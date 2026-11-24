<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('requested_by_position')->nullable();
            $table->string('certified_funds_available')->nullable();
            $table->string('certified_funds_available_position')->nullable();
            $table->string('approved_by_position')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['requested_by_position', 'certified_funds_available', 'certified_funds_available_position', 'approved_by_position']);
        });
    }
};
