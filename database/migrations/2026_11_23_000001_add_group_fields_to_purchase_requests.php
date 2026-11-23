<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // true = this row is a group PR (its items are copies of its member PRs' items)
            $table->boolean('is_group')->default(false)->after('pr_no');
            // set on a member PR = the group PR it was grouped into (null = solo PR)
            $table->foreignId('group_id')->nullable()->after('is_group')
                ->constrained('purchase_requests')->nullOnDelete();
        });

        Schema::table('pr_items', function (Blueprint $table) {
            // on a group PR's items: the original item it was copied from (traceability)
            $table->foreignId('source_item_id')->nullable()->after('purchase_request_id')
                ->constrained('pr_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pr_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_item_id');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn('is_group');
        });
    }
};
