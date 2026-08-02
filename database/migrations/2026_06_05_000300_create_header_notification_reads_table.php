<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('header_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('notification_key');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'notification_key']);
            $table->index(['session_id', 'notification_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('header_notification_reads');
    }
};
