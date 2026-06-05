<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('download_sales', function (Blueprint $table) {
            $table->string('download_type')->default('style')->after('style_sampling_id');
        });

        DB::table('download_sales')
            ->whereNull('style_sampling_id')
            ->where('style_name', 'like', 'Demo Audio:%')
            ->update(['download_type' => 'demo']);
    }

    public function down(): void
    {
        Schema::table('download_sales', function (Blueprint $table) {
            $table->dropColumn('download_type');
        });
    }
};
