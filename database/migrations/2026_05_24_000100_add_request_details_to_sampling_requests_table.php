<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sampling_requests', function (Blueprint $table) {
            $table->unsignedInteger('keyboard_storage_mb')->nullable()->after('pack_name');
            $table->text('customer_notes')->nullable()->after('keyboard_storage_mb');
        });
    }

    public function down(): void
    {
        Schema::table('sampling_requests', function (Blueprint $table) {
            $table->dropColumn(['keyboard_storage_mb', 'customer_notes']);
        });
    }
};
