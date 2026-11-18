<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('rfqs')->cascadeOnDelete();
            // Back-reference to the PR line this row was copied from (null for rows typed by hand).
            // Used only for the coverage line and the "on RFQ ..." tags. Values are copied, not linked.
            $table->unsignedBigInteger('pr_item_id')->nullable();
            $table->foreign('pr_item_id')->references('id')->on('pr_items')->nullOnDelete();
            $table->unsignedInteger('sort_order');
            $table->string('line_type')->default('item');    // item | lot_header | note
            $table->string('stock_no')->nullable();
            $table->string('unit')->nullable();
            $table->text('description');
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('unit_cost', 14, 2)->nullable(); // ABC unit cost
            $table->decimal('amount', 14, 2)->nullable();    // ABC total, qty x unit_cost, server computed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_items');
    }
};
