<?php

namespace App\Http\Controllers;

use App\Models\QuestionCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionCategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer'],
            'questionnaire_id' => ['required', 'integer', 'exists:questionnaires,id'],
        ]);

        $category = new QuestionCategory();
        $category->name = $request->name;
        $category->order = $request->order;
        $category->questionnaire_id = $request->questionnaire_id;
        $category->save();

        return redirect()->route('questionnaires.show', $request->questionnaire_id)->with('success', 'Kategori pertanyaan berhasil dibuat.');
    }

    public function update(Request $request, QuestionCategory $category)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer'],
        ]);

        // Ambil kategori lain yang urutannya sama
        $oldCategory = QuestionCategory::where('questionnaire_id', $category->questionnaire_id)
            ->where('order', $request->order)
            ->first();

        // Jika ditemukan kategori dengan urutan yang sama, tukar urutan mereka
        if ($oldCategory && $oldCategory->id !== $category->id) {
            $oldCategory->order = $category->order;
            $oldCategory->save();
        }

        // Perbarui kategori yang sedang diedit
        $category->name = $request->name;
        $category->order = $request->order;
        $category->save();

        return redirect()->route('questionnaires.show', $category->questionnaire_id)->with('success', 'Kategori pertanyaan berhasil diperbarui.');
    }


    public function destroy(QuestionCategory $category)
    {
        $category->delete();

        return redirect()->route('questionnaires.show', $category->questionnaire_id)->with('success', 'Kategori pertanyaan berhasil dihapus.');
    }
}
