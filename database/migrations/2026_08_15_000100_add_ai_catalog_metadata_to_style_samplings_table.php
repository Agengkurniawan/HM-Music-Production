<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('style_samplings', function (Blueprint $table) {
            $table->string('ai_song_title')->nullable()->after('embedding_updated_at');
            $table->string('ai_artist')->nullable()->after('ai_song_title');
            $table->string('ai_genre')->nullable()->after('ai_artist');
            $table->longText('ai_aliases')->nullable()->after('ai_genre');
            $table->text('ai_search_profile')->nullable()->after('ai_aliases');
            $table->string('ai_enrichment_status')->nullable()->after('ai_search_profile');
            $table->string('ai_enrichment_source')->nullable()->after('ai_enrichment_status');
            $table->string('ai_enrichment_source_hash', 64)->nullable()->after('ai_enrichment_source');
            $table->timestamp('ai_enrichment_updated_at')->nullable()->after('ai_enrichment_source_hash');
        });
    }

    public function down(): void
    {
        Schema::table('style_samplings', function (Blueprint $table) {
            $table->dropColumn([
                'ai_song_title', 'ai_artist', 'ai_genre', 'ai_aliases', 'ai_search_profile',
                'ai_enrichment_status', 'ai_enrichment_source', 'ai_enrichment_source_hash', 'ai_enrichment_updated_at',
            ]);
        });
    }
};
