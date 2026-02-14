<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('respondent_externals', function (Blueprint $table) {
            // Tambah kolom role
            $table->string('role')->after('questionnaire_id'); // 'alumni', 'mitra', 'pengguna_lulusan'

            // Ubah company_or_institution jadi nullable
            $table->string('company_or_institution')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('respondent_externals', function (Blueprint $table) {
            $table->dropColumn('role');
            // Kembalikan ke not null (hati-hati jika sudah ada data null)
            $table->string('company_or_institution')->nullable(false)->change();
        });
    }
};
