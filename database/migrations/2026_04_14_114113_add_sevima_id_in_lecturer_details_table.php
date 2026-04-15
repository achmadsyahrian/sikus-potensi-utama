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
        Schema::table('lecturer_details', function (Blueprint $table) {
            $table->string('sevima_id')->nullable()->after('user_id')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecturer_details', function (Blueprint $table) {
            $table->dropColumn('sevima_id');
        });
    }
};
