<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudy;
use App\Http\Resources\ProgramStudyResource;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Artisan;

class ProgramStudyController extends Controller
{
    public function index(Request $request)
    {
        $query = ProgramStudy::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        
        $programStudies = $query->paginate(10)->withQueryString();
        
        return Inertia::render('ProgramStudies/Index', [
            'programStudies' => $programStudies,
            'filters' => $request->only(['search']),
        ]);
    }
    
    public function sync()
    {
        try {
            Artisan::call('sync:sevima-program-studies');
        } catch (\Exception $e) {
            return back()->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }
        
        return back()->with('success', 'Sinkronisasi data program studi berhasil!');
    }
}