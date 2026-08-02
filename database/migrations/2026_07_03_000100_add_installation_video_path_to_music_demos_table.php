<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('music_demos', function (Blueprint $table) {
            $table->string('installation_video_path')->nullable()->after('installation_youtube_url');
        });
    }

    public function down(): void
    {
        Schema::table('music_demos', function (Blueprint $table) {
            $table->dropColumn('installation_video_path');
        });
    }
};
