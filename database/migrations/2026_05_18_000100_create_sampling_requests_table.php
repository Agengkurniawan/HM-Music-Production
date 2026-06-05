<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sampling_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('style_sampling_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_reference')->unique();
            $table->string('product_name');
            $table->string('pack_name')->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->string('payment_status')->default('Pending');
            $table->string('status')->default('Pending Payment');
            $table->string('n27_file_path')->nullable();
            $table->string('n27_original_name')->nullable();
            $table->timestamp('n27_uploaded_at')->nullable();
            $table->string('google_drive_link')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sampling_requests');
    }
};
