<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function store(Request $request, Questionnaire $questionnaire)
    {
        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:500'],
            'question_type' => ['required', 'string', Rule::in(['multiple_choice', 'text'])],
            'category_id' => ['nullable', 'exists:question_categories,id'],
            'is_required' => ['required', 'boolean'],
        ]);

        $nextOrderQuery = $questionnaire->questions();

        if ($validated['category_id']) {
            $nextOrderQuery->where('category_id', $validated['category_id']);
        } else {
            $nextOrderQuery->whereNull('category_id');
        }

        $nextOrder = $nextOrderQuery->count() + 1;
        $data = array_merge($validated, ['order' => $nextOrder]);
        $questionnaire->questions()->create($data);

        return redirect()->route('questionnaires.show', $questionnaire->id)
            ->with('success', 'Pertanyaan berhasil ditambahkan.');
    }

    public function update(Request $request, Questionnaire $questionnaire, Question $question)
    {
        if ($question->questionnaire_id !== $questionnaire->id) {
            abort(404);
        }

        $originalOrder = $question->order;

        $totalQuestionsInCategory = $questionnaire->questions()
            ->where('category_id', $request->category_id)
            ->count();

        $validated = $request->validate([
            'question_text' => ['required', 'string', 'max:500'],
            'question_type' => ['required', 'string', Rule::in(['multiple_choice', 'text'])],
            'category_id' => ['nullable', 'exists:question_categories,id'],
            'is_required' => ['required', 'boolean'],
            'order' => ['required', 'integer', 'min:1', 'max:' . $totalQuestionsInCategory],
        ]);

        if ($validated['order'] !== $originalOrder) {
            $existingQuestion = $questionnaire->questions()
                ->where('category_id', $validated['category_id'])
                ->where('order', $validated['order'])
                ->first();

            if ($existingQuestion) {
                $existingQuestion->update(['order' => $originalOrder]);
            }
        }

        $question->update($validated);

        return redirect()->route('questionnaires.show', $questionnaire->id)
            ->with('success', 'Pertanyaan berhasil diperbarui.');
    }

    public function destroy(Questionnaire $questionnaire, Question $question)
    {
        if ($question->questionnaire_id !== $questionnaire->id) {
            abort(404);
        }

        $question->delete();

        $questionsToReorder = $questionnaire->questions()
            ->where('category_id', $question->category_id)
            ->where('order', '>', $question->order)
            ->orderBy('order')
            ->get();

        foreach ($questionsToReorder as $q) {
            $q->update(['order' => $q->order - 1]);
        }

        return redirect()->route('questionnaires.show', $questionnaire->id)
            ->with('success', 'Pertanyaan berhasil dihapus.');
    }
}
