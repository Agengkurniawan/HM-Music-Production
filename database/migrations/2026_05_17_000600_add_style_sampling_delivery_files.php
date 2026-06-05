<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('style_samplings', function (Blueprint $table) {
            $table->string('style_file_path')->nullable()->after('status');
            $table->string('sampling_file_path')->nullable()->after('style_file_path');
            $table->string('style_filename')->nullable()->after('cover_image_url');
            $table->string('sampling_filename')->nullable()->after('style_filename');
        });

        DB::table('style_samplings')->orderBy('id')->get()->each(function ($style): void {
            $baseName = str($style->name)->slug('-')->toString();

            DB::table('style_samplings')
                ->where('id', $style->id)
                ->update([
                    'style_filename' => $style->style_filename ?: "{$baseName}.sty",
                    'sampling_filename' => $style->sampling_filename ?: "{$baseName}.ppi",
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('style_samplings', function (Blueprint $table) {
            $table->dropColumn([
                'style_file_path',
                'sampling_file_path',
                'style_filename',
                'sampling_filename',
            ]);
        });
    }
};
