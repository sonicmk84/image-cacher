<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PornstarFeedValidator
{
    const DEVICE_TYPES = ['pc', 'tablet', 'mobile'];
    public static function validatePornstar(array $item): bool
    {
        if (!is_int($item['id'])) {
            Log::warning("Invalid pornstar: {" . $item['name'] . "}, due to invalid id value: " . $item['id']);
            return false;
        }

        // Accepts utf-8 alphabetical characters and certain symbols only.
        if (!preg_match("/^[\pL\s\w\-.'`\"]{1,50}+$/u", $item['name'])) {
            Log::warning("Invalid pornstar: {" . $item['name'] . "}, due to invalid name value: " . $item['name']);
            return false;
        }

        if (!filter_var($item['link'], FILTER_VALIDATE_URL)) {
            Log::warning("Invalid pornstar: {" . $item['name'] . "}, due to invalid link value: " . $item['link']);
            return false;
        }

        if (!preg_match("/^[A-Za-z]{0,20}+$/u", $item['license'])) {
            Log::warning("Invalid pornstar: {" . $item['name'] . "}, due to invalid license value: " . $item['license']);
            return false;
        }

        return true;
    }

    public static function validateThumbnail(array $thumbnail): bool
    {
        if (!isset($thumbnail['urls']) || !is_array($thumbnail['urls']) || empty($thumbnail['urls'])) {
            Log::warning("Empty or invalid urls array");
            return false;
        }

        if (!in_array($thumbnail['type'], self::DEVICE_TYPES)) {
            Log::warning("Invalid thumbnail: {" . $thumbnail['urls'][0] . "}, due to invalid type value: " . $thumbnail['type']);
            return false;
        }

        if (!filter_var($thumbnail['urls'][0], FILTER_VALIDATE_URL)) {
            Log::warning("Invalid thumbnail: {" . $thumbnail['urls'][0] . "}, due to invalid url value.");
            return false;
        }

        return true;
    }
}
