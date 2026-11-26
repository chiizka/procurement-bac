<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One Purchase Order per awarded Abstract. Written by the procurement role only.
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abstract_id')->unique()->constrained('abstracts')->restrictOnDelete();
            $table->string('po_no')->unique();                    // typed, e.g. "2025-07-362"
            $table->date('po_date');
            $table->foreignId('twg_supplier_id')->constrained('twg_suppliers')->restrictOnDelete(); // awarded supplier
            // Supplier details are snapshots so the printed PO never changes when the suppliers list does.
            $table->string('supplier_name');
            $table->string('supplier_address')->nullable();
            $table->string('supplier_tin')->nullable();
            $table->string('supplier_contact_no')->nullable();
            $table->string('procurement_mode');                   // e.g. "SVP"
            $table->string('place_of_delivery')->nullable();      // dropdown (App\Support\PoOptions)
            $table->string('delivery_term')->nullable();          // dropdown
            $table->string('date_of_delivery')->nullable();       // free text, e.g. "1 day"
            $table->string('payment_term')->nullable();           // dropdown
            $table->string('warranty_security')->nullable();      // free text, e.g. "Warranty Security 1%"
            $table->json('deductions')->nullable();              // manual: [{label, rate, amount}]
            $table->decimal('total_amount', 14, 2)->default(0);   // sum of item lines, server computed
            $table->decimal('deductions_total', 14, 2)->default(0);
            $table->decimal('amount_due', 14, 2)->default(0);     // total_amount - deductions_total
            $table->string('approver_name')->nullable();          // left blank by default
            $table->string('approver_position')->nullable();
            $table->string('accountant_name')->nullable();
            $table->string('status')->default('draft');          // draft | created
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Item lines copied from the RFQ with the awarded supplier's TWG prices.
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('rfq_item_id')->nullable();
            $table->foreign('rfq_item_id')->references('id')->on('rfq_items')->nullOnDelete();
            $table->unsignedInteger('sort_order');
            $table->string('line_type')->default('item');         // item | lot_header | note
            $table->string('stock_no')->nullable();
            $table->string('unit')->nullable();
            $table->text('description');
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('unit_cost', 14, 2)->nullable();      // supplier's offered unit price
            $table->decimal('amount', 14, 2)->nullable();         // qty x unit_cost
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
