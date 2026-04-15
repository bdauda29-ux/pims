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
            $table->string('employment_status')->default('Active')->after('role');
            $table->date('retired_at')->nullable()->after('employment_status');
            $table->string('retirement_reason')->nullable()->after('retired_at');
            $table->date('date_of_retirement')->nullable()->after('retirement_reason');
            $table->boolean('must_change_password')->default(false)->after('date_of_retirement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'must_change_password',
                'date_of_retirement',
                'retirement_reason',
                'retired_at',
                'employment_status',
            ]);
        });
    }
};
