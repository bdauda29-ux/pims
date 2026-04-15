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
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete()->after('formation_id');
            $table->string('phone')->nullable()->after('other_names');
            $table->string('nok_name')->nullable()->after('phone');
            $table->string('nok_phone')->nullable()->after('nok_name');
            $table->string('qualification')->nullable()->after('nok_phone');
            $table->string('field_of_study')->nullable()->after('qualification');
            $table->string('salary_account_number')->nullable()->after('field_of_study');
            $table->string('bank')->nullable()->after('salary_account_number');
            $table->string('pfa_number')->nullable()->after('bank');
            $table->string('pfa_name')->nullable()->after('pfa_number');
            $table->string('nhf_no')->nullable()->after('pfa_name');
            $table->string('ippis_no')->nullable()->after('nhf_no');
            $table->text('remark')->nullable()->after('ippis_no');

            $table->date('date_of_present_promotion')->nullable()->after('date_of_present_appointment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropColumn([
                'office_id',
                'phone',
                'nok_name',
                'nok_phone',
                'qualification',
                'field_of_study',
                'salary_account_number',
                'bank',
                'pfa_number',
                'pfa_name',
                'nhf_no',
                'ippis_no',
                'remark',
                'date_of_present_promotion',
            ]);
        });
    }
};
