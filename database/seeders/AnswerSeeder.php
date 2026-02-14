<?php

namespace Database\Seeders;

use App\Models\Questionnaire;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class AnswerSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');

        // Ambil semua ID user untuk performa random yang lebih cepat
        $userIds = User::pluck('id');
        $questionnaires = Questionnaire::with(['questions', 'options'])->get();

        if ($userIds->isEmpty() || $questionnaires->isEmpty()) {
            return;
        }

        DB::table('answers')->truncate();

        $answers = [];
        $totalResponses = 2000; // Jumlah simulasi pengisian kuesioner

        for ($i = 0; $i < $totalResponses; $i++) {
            $randomUserId = $userIds->random();
            $questionnaire = $questionnaires->random();
            $createdAt = $faker->dateTimeBetween('-6 months', 'now');

            $availableOptions = $questionnaire->options;

            foreach ($questionnaire->questions as $question) {
                $answerValue = null;

                if ($question->question_type === 'multiple_choice' && $availableOptions->isNotEmpty()) {
                    $answerValue = $availableOptions->random()->option_value;
                } else {
                    $answerValue = $faker->sentence();
                }

                $answers[] = [
                    'user_id' => $randomUserId,
                    'questionnaire_id' => $questionnaire->id,
                    'question_id' => $question->id,
                    'role_id' => 5, // Statis sesuai request kamu
                    'answer_value' => $answerValue,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];

                // Insert setiap 1000 record agar query tidak terlalu panjang
                if (count($answers) >= 1000) {
                    DB::table('answers')->insert($answers);
                    $answers = [];
                }
            }
        }

        if (count($answers) > 0) {
            DB::table('answers')->insert($answers);
        }
    }
}
