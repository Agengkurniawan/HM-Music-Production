<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('style_sampling_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_name');
            $table->string('style_name');
            $table->string('status')->default('Completed');
            $table->unsignedInteger('amount')->default(0);
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_sales');
    }
};
