<?php

namespace Database\Seeders;

use App\Models\Questionnaire;
use App\Models\QuestionnaireTarget;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\ProgramStudy;
use App\Models\AcademicPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class QuestionnaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('id_ID');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('answers')->truncate();
        DB::table('questionnaire_targets')->truncate();
        DB::table('question_options')->truncate();
        DB::table('questions')->truncate();
        DB::table('questionnaires')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $roles = Role::whereNotIn('slug', ['superadmin'])->get();
        $programStudies = ProgramStudy::all();
        $academicPeriod = AcademicPeriod::inRandomOrder()->first();

        if (!$academicPeriod) {
            echo "Pastikan AcademicPeriodSeeder sudah dijalankan terlebih dahulu.\n";
            return;
        }

        // Kuesioner 1: Kuesioner Kepuasan Layanan Akademik
        $questionnaire1 = Questionnaire::create([
            'name' => 'Kuesioner Kepuasan Layanan Akademik',
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
            'academic_period_id' => $academicPeriod->id,
        ]);
        QuestionnaireTarget::create(['questionnaire_id' => $questionnaire1->id, 'target_type' => 'role', 'target_value' => 'mahasiswa']);

        $q1_question1 = Question::create(['questionnaire_id' => $questionnaire1->id, 'question_text' => 'Bagaimana kualitas layanan akademik di fakultas Anda?', 'question_type' => 'multiple_choice', 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire1->id, 'option_text' => 'Sangat Baik', 'option_value' => 4, 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire1->id, 'option_text' => 'Baik', 'option_value' => 3, 'order' => 2]);
        QuestionOption::create(['questionnaire_id' => $questionnaire1->id, 'option_text' => 'Cukup', 'option_value' => 2, 'order' => 3]);
        QuestionOption::create(['questionnaire_id' => $questionnaire1->id, 'option_text' => 'Kurang', 'option_value' => 1, 'order' => 4]);

        $q1_question2 = Question::create(['questionnaire_id' => $questionnaire1->id, 'question_text' => 'Seberapa mudah akses informasi akademik?', 'question_type' => 'multiple_choice', 'order' => 2]);
        QuestionOption::create(['questionnaire_id' => $questionnaire1->id, 'option_text' => 'Sangat Mudah', 'option_value' => 3, 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire1->id, 'option_text' => 'Mudah', 'option_value' => 2, 'order' => 2]);
        QuestionOption::create(['questionnaire_id' => $questionnaire1->id, 'option_text' => 'Sulit', 'option_value' => 1, 'order' => 3]);
        
        // Kuesioner 2: Survei Kinerja Dosen
        $questionnaire2 = Questionnaire::create([
            'name' => 'Survei Kinerja Dosen',
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(20),
            'is_active' => true,
            'academic_period_id' => $academicPeriod->id,
        ]);
        QuestionnaireTarget::create(['questionnaire_id' => $questionnaire2->id, 'target_type' => 'role', 'target_value' => 'dosen']);

        $q2_question1 = Question::create(['questionnaire_id' => $questionnaire2->id, 'question_text' => 'Apakah Anda puas dengan fasilitas pengajaran yang tersedia?', 'question_type' => 'multiple_choice', 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire2->id, 'option_text' => 'Sangat Puas', 'option_value' => 3, 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire2->id, 'option_text' => 'Puas', 'option_value' => 2, 'order' => 2]);
        QuestionOption::create(['questionnaire_id' => $questionnaire2->id, 'option_text' => 'Kurang Puas', 'option_value' => 1, 'order' => 3]);

        // Kuesioner 3: Evaluasi Lingkungan Kerja Pegawai
        $questionnaire3 = Questionnaire::create([
            'name' => 'Evaluasi Lingkungan Kerja Pegawai',
            'start_date' => now()->subWeeks(2),
            'end_date' => now()->addWeeks(1),
            'is_active' => true,
            'academic_period_id' => $academicPeriod->id,
        ]);
        QuestionnaireTarget::create(['questionnaire_id' => $questionnaire3->id, 'target_type' => 'role', 'target_value' => 'pegawai']);

        $q3_question1 = Question::create(['questionnaire_id' => $questionnaire3->id, 'question_text' => 'Bagaimana lingkungan kerja di unit Anda?', 'question_type' => 'multiple_choice', 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire3->id, 'option_text' => 'Sangat Baik', 'option_value' => 3, 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire3->id, 'option_text' => 'Baik', 'option_value' => 2, 'order' => 2]);
        QuestionOption::create(['questionnaire_id' => $questionnaire3->id, 'option_text' => 'Cukup', 'option_value' => 1, 'order' => 3]);

        // Kuesioner 4: Kuesioner untuk Mitra
        $questionnaire4 = Questionnaire::create([
            'name' => 'Kuesioner Kolaborasi dengan Mitra',
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(15),
            'is_active' => true,
            'academic_period_id' => $academicPeriod->id,
        ]);
        QuestionnaireTarget::create(['questionnaire_id' => $questionnaire4->id, 'target_type' => 'role', 'target_value' => 'mitra']);
        if ($programStudies->isNotEmpty()) {
            QuestionnaireTarget::create(['questionnaire_id' => $questionnaire4->id, 'target_type' => 'program_study', 'target_value' => $programStudies->first()->id]);
        }
        $q4_question1 = Question::create(['questionnaire_id' => $questionnaire4->id, 'question_text' => 'Bagaimana pengalaman Anda bekerja sama dengan universitas kami?', 'question_type' => 'multiple_choice', 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire4->id, 'option_text' => 'Sangat Memuaskan', 'option_value' => 3, 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire4->id, 'option_text' => 'Memuaskan', 'option_value' => 2, 'order' => 2]);
        QuestionOption::create(['questionnaire_id' => $questionnaire4->id, 'option_text' => 'Kurang Memuaskan', 'option_value' => 1, 'order' => 3]);

        // Kuesioner 5: Kuesioner Umum untuk Semua Role
        $questionnaire5 = Questionnaire::create([
            'name' => 'Kuesioner Umum Universitas',
            'start_date' => now()->subWeeks(3),
            'end_date' => now()->addWeek(),
            'is_active' => true,
            'academic_period_id' => $academicPeriod->id,
        ]);
        QuestionnaireTarget::create(['questionnaire_id' => $questionnaire5->id, 'target_type' => 'all', 'target_value' => '']); // Mengubah null menjadi string kosong
        $q5_question1 = Question::create(['questionnaire_id' => $questionnaire5->id, 'question_text' => 'Bagaimana kualitas infrastruktur universitas secara umum?', 'question_type' => 'multiple_choice', 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire5->id, 'option_text' => 'Sangat Baik', 'option_value' => 3, 'order' => 1]);
        QuestionOption::create(['questionnaire_id' => $questionnaire5->id, 'option_text' => 'Baik', 'option_value' => 2, 'order' => 2]);
        QuestionOption::create(['questionnaire_id' => $questionnaire5->id, 'option_text' => 'Cukup', 'option_value' => 1, 'order' => 3]);
        
        echo "Berhasil membuat 5 kuesioner dummy dengan pertanyaan dan target yang bervariasi.\n";
    }
}
