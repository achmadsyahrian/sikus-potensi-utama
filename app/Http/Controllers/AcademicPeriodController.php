<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class AcademicPeriodController extends Controller
{
    public function index(Request $request)
    {
        $query = AcademicPeriod::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $academicPeriods = $query->latest('academic_year')->paginate(10)->withQueryString();

        return Inertia::render('AcademicPeriods/Index', [
            'academicPeriods' => $academicPeriods,
            'filters' => $request->only(['search']),
        ]);
    }

    public function sync()
    {
        try {
            Artisan::call('sync:sevima-academic-periods');
        } catch (\Exception $e) {
            return redirect()->route('academic-periods.index')->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }

        return redirect()->route('academic-periods.index')->with('success', 'Sinkronisasi data periode akademik berhasil!');
    }
}
