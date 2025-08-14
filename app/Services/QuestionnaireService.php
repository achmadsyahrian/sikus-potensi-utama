<?php

namespace App\Services;

use App\Models\Faculty;
use App\Models\ProgramStudy;
use App\Models\Questionnaire;
use Illuminate\Support\Facades\DB;

class QuestionnaireService
{
    public function createQuestionnaire(array $data): Questionnaire
    {
        return DB::transaction(function () use ($data) {
            $questionnaire = Questionnaire::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'academic_period_id' => $data['academic_period_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            $this->syncTargets($questionnaire, $data['targets']);
            
            return $questionnaire;
        });
    }

    public function updateQuestionnaire(Questionnaire $questionnaire, array $data): Questionnaire
    {
        return DB::transaction(function () use ($questionnaire, $data) {
            if (!$questionnaire->is_active && isset($data['is_active']) && $data['is_active']) {
                $this->ensureReadyForActivation($questionnaire);
            }

            $questionnaire->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'academic_period_id' => $data['academic_period_id'],
                'is_active' => $data['is_active'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            $this->syncTargets($questionnaire, $data['targets']);
            
            return $questionnaire;
        });
    }

    protected function syncTargets(Questionnaire $questionnaire, array $targets)
    {
        $targetsCollection = collect($targets);

        $finalTargets = $targetsCollection->reject(function ($target) use ($targetsCollection) {
            if ($target['target_type'] === 'program_study') {
                $programStudy = ProgramStudy::find($target['target_value']);
                if ($programStudy) {
                    $faculty = Faculty::where('faculty_code', $programStudy->faculty_code)->first();
                    return $targetsCollection->contains(function ($t) use ($faculty) {
                        return $t['target_type'] === 'faculty' && $t['target_value'] == $faculty->id;
                    });
                }
            }
            return false;
        });

        $questionnaire->targets()->delete();
        foreach ($finalTargets as $target) {
            $questionnaire->targets()->create([
                'target_type' => $target['target_type'],
                'target_value' => $target['target_value'],
            ]);
        }
    }

    /**
     * Memastikan kuesioner memiliki pertanyaan dan opsi sebelum diaktifkan.
     */
    protected function ensureReadyForActivation(Questionnaire $questionnaire)
    {
        if ($questionnaire->questions()->count() === 0 || $questionnaire->options()->count() === 0) {
            throw new \Exception('Kuesioner harus memiliki setidaknya satu pertanyaan dan satu opsi jawaban untuk dapat diaktifkan.');
        }
    }
}