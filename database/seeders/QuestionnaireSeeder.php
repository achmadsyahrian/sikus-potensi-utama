<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionCategory;
use App\Models\Questionnaire;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('1. Setup Data Pendukung...');

            // Setup Periode
            $period = AcademicPeriod::firstOrCreate(
                ['name' => '2025/2026 Ganjil'],
                [
                    'academic_year' => '2025/2026',
                    'semester' => 'Ganjil',
                    'start_date' => now()->subMonth(),
                    'end_date' => now()->addMonths(5),
                    'is_active' => true
                ]
            );

            // Setup User Dummy (Responden)
            $respondents = User::factory()->count(5)->create([
                'password' => Hash::make('password'),
            ]);

            // DATA MASTER KUESIONER
            $questionnairesData = [
                [
                    'name' => 'Penilaian Kelayakan Proposal 2026',
                    'description' => 'Instrumen penilaian usulan penelitian dengan Skala 1-5.',
                    // OPSI JAWABAN (Berlaku untuk SEMUA pertanyaan di kuesioner ini)
                    'global_options' => [
                        ['text' => 'Sangat Kurang', 'value' => 1],
                        ['text' => 'Kurang', 'value' => 2],
                        ['text' => 'Cukup', 'value' => 3],
                        ['text' => 'Baik', 'value' => 4],
                        ['text' => 'Sangat Baik', 'value' => 5],
                    ],
                    'categories' => [
                        [
                            'name' => 'Administrasi',
                            'questions' => [
                                ['text' => 'Kelengkapan dokumen administrasi pengusul?', 'type' => 'multiple_choice'],
                                ['text' => 'Kesesuaian format proposal dengan panduan?', 'type' => 'multiple_choice'],
                            ]
                        ],
                        [
                            'name' => 'Substansi',
                            'questions' => [
                                ['text' => 'Kebaruan (Novelty) penelitian yang diajukan?', 'type' => 'multiple_choice'],
                                ['text' => 'Ketajaman perumusan masalah?', 'type' => 'multiple_choice'],
                                ['text' => 'Berikan catatan khusus terkait metodologi (jika ada).', 'type' => 'text'], // Essay
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'Evaluasi Laporan Akhir (Ya/Tidak)',
                    'description' => 'Checklist kelengkapan laporan akhir.',
                    // OPSI JAWABAN (Hanya Ya/Tidak)
                    'global_options' => [
                        ['text' => 'Tidak Ada', 'value' => 0],
                        ['text' => 'Ada / Lengkap', 'value' => 1],
                    ],
                    'categories' => [
                        [
                            'name' => 'Luaran Wajib',
                            'questions' => [
                                ['text' => 'Artikel Ilmiah (Draft/Published)?', 'type' => 'multiple_choice'],
                                ['text' => 'Dokumentasi Kegiatan?', 'type' => 'multiple_choice'],
                                ['text' => 'Laporan Keuangan 100%?', 'type' => 'multiple_choice'],
                            ]
                        ]
                    ]
                ]
            ];

            // EKSEKUSI LOOP
            foreach ($questionnairesData as $qData) {
                $this->command->info("Membuat Kuesioner: {$qData['name']}");

                // 1. Buat Kuesioner
                $questionnaire = Questionnaire::create([
                    'academic_period_id' => $period->id,
                    'name' => $qData['name'],
                    'description' => $qData['description'],
                    'is_active' => true,
                    'start_date' => now(),
                    'end_date' => now()->addMonth(),
                    'public_link_token' => Str::random(32),
                ]);

                // 2. Buat Opsi Jawaban (Link ke Questionnaire)
                foreach ($qData['global_options'] as $idx => $opt) {
                    QuestionOption::create([
                        'questionnaire_id' => $questionnaire->id, // Global Option
                        'option_text' => $opt['text'],
                        'option_value' => $opt['value'],
                        'order' => $idx + 1,
                    ]);
                }

                // 3. Buat Kategori & Pertanyaan
                foreach ($qData['categories'] as $cIndex => $cData) {
                    $category = QuestionCategory::create([
                        'questionnaire_id' => $questionnaire->id,
                        'name' => $cData['name'],
                        'order' => $cIndex + 1,
                    ]);

                    foreach ($cData['questions'] as $qIndex => $qDetails) {
                        Question::create([
                            'questionnaire_id' => $questionnaire->id,
                            'category_id' => $category->id,
                            'question_text' => $qDetails['text'],
                            'question_type' => $qDetails['type'],
                            'order' => $qIndex + 1,
                            'is_required' => true,
                        ]);
                    }
                }

                // 4. Generate Jawaban Dummy (Answers)
                $this->command->info("  -> Mengisi jawaban dummy...");

                // Ambil ulang opsi yang baru dibuat untuk kuesioner ini
                $availableOptions = $questionnaire->options;
                $allQuestions = $questionnaire->questions;

                foreach ($respondents as $user) {
                    foreach ($allQuestions as $question) {
                        $answerValue = null;

                        if ($question->question_type === 'multiple_choice') {
                            // Ambil salah satu opsi dari kuesioner ini secara acak
                            $randomOption = $availableOptions->random();

                            // Simpan value (skor)
                            $answerValue = (string) $randomOption->option_value;
                        } else {
                            // Essay
                            $answerValue = "Catatan review dummy dari {$user->name}...";
                        }

                        Answer::create([
                            'user_id' => $user->id,
                            'questionnaire_id' => $questionnaire->id,
                            'question_id' => $question->id,
                            'answer_value' => $answerValue,
                        ]);
                    }
                }
            }
        });

        $this->command->info('Seeding Selesai! Struktur Shared Options berhasil diterapkan.');
    }
}
