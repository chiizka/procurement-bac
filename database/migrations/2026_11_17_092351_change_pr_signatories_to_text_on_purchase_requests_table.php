<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['requested_by', 'approved_by']);
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('requested_by')->nullable()->after('total_amount');
            $table->string('approved_by')->nullable()->after('requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['requested_by', 'approved_by']);
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('requested_by')->nullable()->constrained('signatories')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('signatories')->nullOnDelete();
        });
    }
};