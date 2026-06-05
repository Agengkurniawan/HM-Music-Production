<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('music_demos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('genre');
            $table->unsignedSmallInteger('bpm');
            $table->string('duration')->default('0:00');
            $table->string('key_signature')->nullable();
            $table->string('access')->default('Free');
            $table->string('status')->default('Published');
            $table->boolean('is_trending')->default(false);
            $table->string('audio_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->unsignedInteger('plays_count')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('music_demos');
    }
};
