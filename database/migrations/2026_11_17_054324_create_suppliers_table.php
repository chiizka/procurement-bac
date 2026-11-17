<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // business name as printed on PO/JO
            $table->string('address');
            $table->string('tin')->nullable();            // e.g. 004-207-840-000
            $table->string('contact_no')->nullable();
            $table->boolean('vat_registered')->nullable(); // possible driver for tax scheme (unconfirmed)
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};