<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('style_samplings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->string('pack')->nullable();
            $table->string('access')->default('Premium');
            $table->string('status')->default('Draft');
            $table->string('audio_path')->nullable();
            $table->string('preview_path')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('audio_filename')->nullable();
            $table->string('preview_filename')->nullable();
            $table->string('file_size')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('downloads_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('style_samplings');
    }
};
