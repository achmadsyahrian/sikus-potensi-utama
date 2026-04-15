<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('respondent_externals', function (Blueprint $table) {
            $table->string('program_study_code')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('respondent_externals', function (Blueprint $table) {
            $table->dropColumn('program_study_code');
        });
    }
};
