<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Run the migrations.
    public function up()
    {
        Schema::create('pornstars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('link');
            $table->json('attributes');
            $table->json('aliases');
            $table->string('license');
            $table->integer('wl_status');
            $table->timestamps();
        });

        Schema::create('thumbnails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pornstar_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('url');
            $table->string('local_path')->nullable();
            $table->integer('width');
            $table->integer('height');
            $table->timestamps();
            $table->unique(['pornstar_id', 'type', 'url']);
        });
    }

    // Reverse the migrations.
    public function down(): void
    {
        Schema::dropIfExists('thumbnails');
        Schema::dropIfExists('pornstars');
    }
};
