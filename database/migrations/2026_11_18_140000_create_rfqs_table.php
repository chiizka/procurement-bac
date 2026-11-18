<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            // Many RFQs per PR: NOT unique.
            $table->foreignId('purchase_request_id')->constrained('purchase_requests');
            $table->string('rfq_no', 50)->unique();          // typed by BAC, e.g. 601-2026
            $table->string('lot_label')->nullable();         // e.g. "Lot 3 - Fishing"; tells sibling RFQs apart
            $table->date('rfq_date');
            $table->dateTime('deadline_at')->nullable();     // required by the FormRequest before Created
            $table->text('purpose');                         // copied from the PR, editable
            $table->string('bac_chairperson')->nullable();   // plain text for now
            $table->string('contract_duration')->nullable(); // free text
            $table->string('delivery_area')->nullable();     // free text
            $table->decimal('total_amount', 14, 2)->default(0); // ABC total, computed by the server
            $table->string('status')->default('draft');      // draft | created
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};
