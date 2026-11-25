<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abstracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('twg_report_id')->unique()->constrained('twg_reports')->restrictOnDelete();
            $table->string('abstract_no')->unique();
            $table->date('abstract_date');
            $table->text('project_name');
            $table->string('project_location')->nullable();
            $table->string('implementing_office')->nullable();
            $table->string('procurement_mode');
            $table->decimal('abc', 14, 2);
            $table->dateTime('bid_deadline_at')->nullable();
            $table->json('evaluations')->nullable();
            $table->string('recommendation')->nullable();
            $table->text('award_description')->nullable();
            $table->decimal('award_amount', 14, 2)->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('signatories')->nullable();
            $table->string('hope_name')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abstracts');
    }
};
