<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One TWG evaluation report per RFQ (an RFQ = one lot).
        Schema::create('twg_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->unique()->constrained('rfqs')->cascadeOnDelete();
            $table->string('twg_no')->unique();                 // typed, e.g. "2025 - 824"
            $table->date('twg_date');
            $table->text('project_name');                       // starts as the RFQ purpose
            $table->decimal('abc', 14, 2)->default(0);          // snapshot of the RFQ total
            $table->string('procurement_mode');                 // e.g. "Section 53.9 - Small Value Procurement"
            $table->string('philgeps_ref')->nullable();         // "Not Available" is a normal value
            $table->string('philgeps_posted')->nullable();
            $table->string('criteria');                         // e.g. "Single Calculated and Responsive Quotation (SCRQ)"
            $table->json('findings')->nullable();               // [{scope, supplier, text}]
            $table->json('signatories')->nullable();            // [{group: evaluated|reviewed, name, position}]
            $table->string('status')->default('draft');         // draft | created
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Supplier columns of the quotation grid.
        Schema::create('twg_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('twg_report_id')->constrained('twg_reports')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('name');
            $table->string('remarks')->nullable();              // SCQ | Failed | ...
            $table->timestamps();
        });

        // One price per supplier per RFQ item. No row = "No Quotation".
        Schema::create('twg_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('twg_supplier_id')->constrained('twg_suppliers')->cascadeOnDelete();
            $table->foreignId('rfq_item_id')->constrained('rfq_items')->cascadeOnDelete();
            $table->decimal('unit_price', 14, 2);               // offered price per unit; total = qty x unit_price
            $table->timestamps();

            $table->unique(['twg_supplier_id', 'rfq_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twg_quotes');
        Schema::dropIfExists('twg_suppliers');
        Schema::dropIfExists('twg_reports');
    }
};
