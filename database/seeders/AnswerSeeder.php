<?php

namespace Database\Seeders;

use App\Models\Questionnaire;
use App\Models\User;
use App\Models\Question;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('id_ID');

        $users = User::all();
        $roles = Role::whereNotIn('slug', ['superadmin', 'admin'])->get();
        $questionnaires = Questionnaire::all();
        
        if ($users->isEmpty() || $questionnaires->isEmpty() || $roles->isEmpty()) {
            echo "Pastikan user, kuesioner, dan role sudah ada sebelum menjalankan seeder ini.\n";
            return;
        }

        // Hapus data jawaban lama
        DB::table('answers')->truncate();

        // Buat 500 data jawaban dummy
        for ($i = 0; $i < 500; $i++) {
            $randomUser = $users->random();
            $randomQuestionnaire = $questionnaires->random();
            $randomRole = $roles->random();

            // Ambil semua pertanyaan dari kuesioner yang dipilih secara acak
            $questions = Question::where('questionnaire_id', $randomQuestionnaire->id)->get();
            
            if ($questions->isEmpty()) {
                continue;
            }
            
            // Buat created_at yang acak dalam rentang 1 tahun terakhir
            $createdAt = $faker->dateTimeBetween('-1 year', 'now');

            foreach ($questions as $question) {
                DB::table('answers')->insert([
                    'user_id' => $randomUser->id,
                    'questionnaire_id' => $randomQuestionnaire->id,
                    'question_id' => $question->id,
                    'role_id' => $randomRole->id,
                    'answer_value' => 'Jawaban dummy untuk pertanyaan ' . $question->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }

        echo "Berhasil membuat 500 data jawaban dummy dengan created_at dan role yang bervariasi.\n";
    }
}
