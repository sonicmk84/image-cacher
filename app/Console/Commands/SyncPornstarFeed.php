<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Pornstar;
use App\Models\Thumbnail;
use App\Services\PornstarFeedValidator;

class SyncPornstarFeed extends Command
{
    protected $signature = 'sync:pornstar-feed';
    protected $description = 'Download pornstar data and cache thumbnails locally';

    const PORNSTAR_FEED_URL = 'https://ph-c3fuhehkfqh6huc0.z01.azurefd.net/feed_pornstars.json';
    const INSERT_CHUNK_SIZE = 500;
    const IMAGE_POOL_SIZE = 100;

    public function handle()
    {
        ini_set('memory_limit', '1G');
        ini_set('max_execution_time', 7200);

        $this->info('Fetching JSON feed...');

        $response = Http::get(self::PORNSTAR_FEED_URL);
        if ($response->failed()) {
            $this->error('Failed to download feed.');
            return Command::FAILURE;
        }

        $rawJson = $response->body();

        // Replace invalid \x escapes
        $cleanJson = preg_replace('/\\\\x[0-9A-Fa-f]{2}/', '', $rawJson);
        $cleanJson = mb_convert_encoding($cleanJson, 'UTF-8', 'UTF-8');

        Storage::disk('local')->put('feed_debug.json', $cleanJson);

        $feed = json_decode($cleanJson, true);

        // Filter valid pornstar and valid thumbnail records.
        $validPornstars = [];
        $validThumbnails = [];
        foreach ($feed['items'] as $item) {
            if (!PornstarFeedValidator::validatePornstar($item)) {
                continue;
            }

            $validPornstars[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'link' => $item['link'],
                'attributes' => json_encode($item['attributes']),
                'aliases' => json_encode($item['aliases']),
                'license' => $item['license'],
                'wl_status' => intval($item['wlStatus']),
            ];

            $thumbnails = $item['thumbnails'];
            foreach ($thumbnails as $thumbnail) {
                if (!PornstarFeedValidator::validateThumbnail($thumbnail)) {
                    continue;
                }

                $validThumbnails[] = [
                    'pornstar_id' => $item['id'],
                    'type' => $thumbnail['type'],
                    'url' => $thumbnail['urls'][0],
                    'width' => intval($thumbnail['width']),
                    'height' => intval($thumbnail['height']),
                ];
            }
        }

        // Split valid pornstars into chunks and insert to table. If id already exists, update insdead.
        $chunksPornstars = array_chunk($validPornstars, self::INSERT_CHUNK_SIZE);

        $this->info("\nInserting/updating pornstar records: ");
        $bar = $this->output->createProgressBar(count($chunksPornstars));
        $bar->start();

        foreach ($chunksPornstars as $chunk) {
            usleep(20000);
            Pornstar::upsert(
                $chunk,
                ['id'],
                ['name', 'link', 'attributes', 'aliases', 'license', 'wl_status']
            );
            $bar->advance();
        }
        $bar->finish();

        // Split valid thumbnails into chunks and insert to table. Or update if found based on unique constraint.
        $chunksThumbnails = array_chunk($validThumbnails, self::INSERT_CHUNK_SIZE);

        $this->info("\nInserting/updating thumbnail records: ");
        $bar = $this->output->createProgressBar(count($chunksThumbnails));
        $bar->start();

        foreach ($chunksThumbnails as $chunk) {
            usleep(20000);
            Thumbnail::upsert(
                $chunk,
                ['pornstar_id', 'type', 'url'],
                ['width', 'height']
            );
            $bar->advance();
        }
        $bar->finish();

        // Gather distinct urls of thumbnails (so that we generate a local image only once).
        $uniqueURLs = Thumbnail::whereNull('local_path')
            ->distinct()
            ->pluck('url')
            ->toArray();

        if (count($uniqueURLs) > 0) {
            // Download images and save their filename in thumbnails table.
            $this->downloadImages($uniqueURLs);
        }

        $this->info("\nSync completed successfully.");

        return Command::SUCCESS;
    }

    private function downloadImages($imageUrls)
    {
        $this->info("\nStarting to download images:");

        $chunks = array_chunk($imageUrls, self::IMAGE_POOL_SIZE);

        $bar = $this->output->createProgressBar(count($chunks));
        $bar->start();

        foreach ($chunks as $chunk) {
            $responses = Http::pool(
                fn($pool) =>
                collect($chunk)->map(fn($url) => $pool->as($url)->get($url))->all()
            );

            $updates = [];

            foreach ($responses as $url => $response) {
                if ($response instanceof \Illuminate\Http\Client\Response && $response->ok()) {
                    // Successful HTTP response
                    $filename = 'thumbnails/' . Str::uuid() . '.' . pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                    Storage::disk('public')->put($filename, $response->body());

                    $updates[] = [
                        'url' => $url,
                        'local_path' => $filename,
                    ];

                } elseif ($response instanceof \Illuminate\Http\Client\RequestException) {
                    // Failed to connect or DNS/SSL issues
                    Log::warning("Connection exception while downloading $url: " . $response->getMessage());

                } elseif ($response instanceof \Illuminate\Http\Client\Response) {
                    // HTTP error (e.g. 404, 500)
                    Log::warning("Failed to download $url (status: {$response->status()})");

                } else {
                    // Unexpected type (shouldn't happen often)
                    Log::warning("Unexpected response type for $url: " . get_class($response));
                }
            }

            if (count($updates) > 0) {
                $this->updateLocalPaths($updates);
            }

            $bar->advance();
        }

        $bar->finish();

        $this->info("\nDownloadImages completed.");
    }

    private function updateLocalPaths($updates)
    {
        $caseSql = '';
        $urls = [];

        foreach ($updates as $update) {
            $url = addslashes($update['url']);
            $path = addslashes($update['local_path']);
            $caseSql .= "WHEN url = '$url' THEN '$path' ";
            $urls[] = "'$url'";
        }

        $urlsList = implode(',', $urls);

        $sql = "
            UPDATE thumbnails
            SET local_path = CASE $caseSql END
            WHERE url IN ($urlsList)
        ";

        DB::statement($sql);
    }
}