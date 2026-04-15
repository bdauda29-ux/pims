<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
            $table->string('type')->nullable()->after('name');

            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'type']);
        });
    }
};
