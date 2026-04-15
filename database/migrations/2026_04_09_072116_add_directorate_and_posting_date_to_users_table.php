<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('directorate_id')->nullable()->constrained()->nullOnDelete()->after('office_id');
            $table->date('date_of_present_posting_to_formation')->nullable()->after('date_of_present_appointment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['directorate_id']);
            $table->dropColumn(['directorate_id', 'date_of_present_posting_to_formation']);
        });
    }
};
