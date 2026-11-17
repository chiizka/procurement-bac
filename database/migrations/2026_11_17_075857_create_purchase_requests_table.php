<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_no')->unique();               // TYPED by BAC; 2026-11-1278 or LIV-2026-08-061
            $table->date('pr_date');
            $table->string('entity_name')->default('DOLE RO5');
            $table->string('fund_cluster')->nullable();       // e.g. 101
            $table->string('office_section');                 // requesting office/section (CSPO, TSSD...)
            $table->string('responsibility_center_code')->nullable();
            $table->text('purpose');
            $table->string('end_user')->nullable();           // printed on the RFQ
            $table->decimal('total_amount', 14, 2)->default(0); // computed from items
            $table->foreignId('requested_by')->nullable()->constrained('signatories')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('signatories')->nullOnDelete();
            $table->string('status')->default('draft');       // draft | created
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};