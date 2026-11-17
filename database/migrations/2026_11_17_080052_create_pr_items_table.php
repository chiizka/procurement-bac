<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pr_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);  // keeps printed order
            $table->string('line_type')->default('item');       // item | lot_header | note
            $table->string('stock_no')->nullable();
            $table->string('unit')->nullable();
            $table->text('description');
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->decimal('amount', 14, 2)->nullable();       // qty x unit_cost
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_items');
    }
};