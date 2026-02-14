<?php

namespace App\Http\Controllers;

use App\Models\SatisfactionCriterion;
use App\Services\SatisfactionCriteriaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SatisfactionCriteriaController extends Controller
{
    protected $service;

    public function __construct(SatisfactionCriteriaService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $query = SatisfactionCriterion::query();

        if ($request->filled('search')) {
            $query->where('label', 'like', "%{$request->search}%");
        }

        return Inertia::render('SatisfactionCriteria/Index', [
            'criteria' => $query->orderBy('min_value')->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('SatisfactionCriteria/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'min_value' => 'required|numeric|min:0|max:100',
            'max_value' => 'required|numeric|min:0|max:100|gt:min_value',
            'color' => 'nullable|string|max:20',
        ]);

        $this->service->createCriteria($validated);

        return redirect()->route('satisfaction-criteria.index')
            ->with('success', 'Kriteria kepuasan berhasil ditambahkan!');
    }

    public function edit(SatisfactionCriterion $satisfactionCriterion)
    {
        return Inertia::render('SatisfactionCriteria/Edit', [
            'criterion' => $satisfactionCriterion
        ]);
    }

    public function update(Request $request, SatisfactionCriterion $satisfactionCriterion)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'min_value' => 'required|numeric|min:0|max:100',
            'max_value' => 'required|numeric|min:0|max:100|gt:min_value',
            'color' => 'nullable|string|max:20',
        ]);

        $this->service->updateCriteria($satisfactionCriterion, $validated);

        return redirect()->route('satisfaction-criteria.index')
            ->with('success', 'Kriteria kepuasan berhasil diperbarui!');
    }

    public function destroy(SatisfactionCriterion $satisfactionCriterion)
    {
        $this->service->deleteCriteria($satisfactionCriterion);

        return redirect()->route('satisfaction-criteria.index')
            ->with('success', 'Kriteria kepuasan berhasil dihapus!');
    }
}
