<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->string('sku', 40)->unique();
            $table->string('shape', 30);
            $table->decimal('carat', 6, 2);
            $table->string('color', 3);
            $table->string('clarity', 4);
            $table->string('cut', 12)->nullable();
            $table->string('lab', 10)->nullable();
            $table->string('certificate_no', 40)->nullable();
            $table->decimal('price_per_carat', 10, 2);
            $table->decimal('total_price', 12, 2);
            $table->string('status', 20)->default('available');
            $table->string('image_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'shape']);
            $table->index(['carat', 'total_price']);
            $table->index(['color', 'clarity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stones');
    }
};
