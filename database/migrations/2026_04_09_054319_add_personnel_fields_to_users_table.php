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
            $table->string('nis_no')->unique()->nullable();
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('other_names')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
            $table->foreignId('lga_id')->nullable()->constrained('lgas')->onDelete('set null');
            $table->date('date_of_first_appointment')->nullable();
            $table->date('date_of_present_appointment')->nullable();
            $table->foreignId('formation_id')->nullable()->constrained('formations')->onDelete('set null');
            $table->string('role')->default('User'); // Main Admin, Formation Admin, Office Admin, User
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['lga_id']);
            $table->dropForeign(['formation_id']);
            $table->dropColumn([
                'nis_no', 'surname', 'first_name', 'other_names', 'gender',
                'date_of_birth', 'state_id', 'lga_id', 'date_of_first_appointment',
                'date_of_present_appointment', 'formation_id', 'role',
            ]);
        });
    }
};
