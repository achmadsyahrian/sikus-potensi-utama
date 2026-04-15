<?php
namespace App\Http\Controllers;

use App\Models\Questionnaire;
use Inertia\Inertia;

class ExternalQuestionnaireController extends Controller
{
    public function show($token)
    {
        $questionnaire = Questionnaire::with(['categories.questions', 'questionsWithoutCategory', 'options', 'academicPeriod'])
            ->where('public_link_token', $token)
            ->firstOrFail();

        if (! $questionnaire->is_active || now()->lt($questionnaire->start_date) || now()->gt($questionnaire->end_date)) {
            abort(404, 'Kuesioner tidak aktif atau sudah kadaluarsa.');
        }

        $allQuestions = $questionnaire->categories->flatMap(fn($cat) => $cat->questions)
            ->merge($questionnaire->questionsWithoutCategory)
            ->sortBy('order')
            ->values();

        $questionnaire->unsetRelation('categories');
        $questionnaire->unsetRelation('questionsWithoutCategory');

        $programStudies = \App\Models\ProgramStudy::where('is_active', true)
            ->orderBy('degree_level')
            ->orderBy('name')
            ->get(['program_study_code', 'name', 'degree_level']);

        return Inertia::render('ExternalQuestionnaire/Show', [
            'questionnaire'  => $questionnaire,
            'allQuestions'   => $allQuestions,
            'programStudies' => $programStudies,
        ]);
    }
}
