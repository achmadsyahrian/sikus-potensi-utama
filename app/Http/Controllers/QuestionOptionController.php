<?php

namespace App\Http\Controllers;

use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionOptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'questionnaire_id' => ['required', 'integer', 'exists:questionnaires,id'],
            'option_text' => ['required', 'string', 'max:255'],
            'option_value' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('question_options')->where(function ($query) use ($request) {
                    return $query->where('questionnaire_id', $request->questionnaire_id);
                }),
            ],
            'order' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('question_options')->where(function ($query) use ($request) {
                    return $query->where('questionnaire_id', $request->questionnaire_id);
                }),
            ],
        ]);

        $option = new QuestionOption();
        $option->questionnaire_id = $request->questionnaire_id;
        $option->option_text = $request->option_text;
        $option->option_value = $request->option_value;
        $option->order = $request->order;
        $option->save();

        return redirect()->route('questionnaires.show', $request->questionnaire_id)->with('success', 'Opsi jawaban berhasil dibuat.');
    }

    public function update(Request $request, QuestionOption $option)
    {
        $request->validate([
            'option_text' => ['required', 'string', 'max:255'],
            'option_value' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('question_options')->where(function ($query) use ($option) {
                    return $query->where('questionnaire_id', $option->questionnaire_id);
                })->ignore($option->id),
            ],
            'order' => ['required', 'integer', 'min:1'],
        ]);

        // Ambil opsi yang urutannya sama
        $oldOption = QuestionOption::where('questionnaire_id', $option->questionnaire_id)
            ->where('order', $request->order)
            ->first();

        // Jika ditemukan opsi dengan urutan yang sama, tukar urutan mereka
        if ($oldOption && $oldOption->id !== $option->id) {
            $oldOption->order = $option->order;
            $oldOption->save();
        }

        // Perbarui opsi yang sedang diedit
        $option->option_text = $request->option_text;
        $option->option_value = $request->option_value;
        $option->order = $request->order;
        $option->save();

        return redirect()->route('questionnaires.show', $option->questionnaire_id)->with('success', 'Opsi jawaban berhasil diperbarui.');
    }

    public function destroy(QuestionOption $option)
    {
        $option->delete();

        return redirect()->route('questionnaires.show', $option->questionnaire_id)->with('success', 'Opsi jawaban berhasil dihapus.');
    }
}
