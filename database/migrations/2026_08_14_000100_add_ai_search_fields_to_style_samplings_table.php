<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('style_samplings', function (Blueprint $table) {
            $table->longText('search_embedding')->nullable()->after('description');
            $table->string('embedding_model')->nullable()->after('search_embedding');
            $table->string('embedding_source_hash', 64)->nullable()->after('embedding_model');
            $table->timestamp('embedding_updated_at')->nullable()->after('embedding_source_hash');
        });
    }

    public function down(): void
    {
        Schema::table('style_samplings', function (Blueprint $table) {
            $table->dropColumn(['search_embedding', 'embedding_model', 'embedding_source_hash', 'embedding_updated_at']);
        });
    }
};
