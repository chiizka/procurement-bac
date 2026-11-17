<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatories', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // e.g. IMELDA F. GATINAO
            $table->string('position');                   // Regional Director, Accountant III, BAC Chair...
            $table->string('role_key');                   // rd | ard | accountant | bac_chair | bac_vice | bac_member | twg_evaluator
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('role_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatories');
    }
};