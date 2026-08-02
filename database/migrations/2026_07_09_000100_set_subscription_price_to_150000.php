<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'subscription_price'],
            ['value' => '150000', 'created_at' => now(), 'updated_at' => now()],
        );

        DB::table('site_settings')->updateOrInsert(
            ['key' => 'payment_gateway'],
            ['value' => 'Midtrans', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'subscription_price')->update([
            'value' => '29000',
            'updated_at' => now(),
        ]);
    }
};
