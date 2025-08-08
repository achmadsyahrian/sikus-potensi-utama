<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Artisan;

class FacultyController extends Controller
{
    public function index(Request $request)
    {
        $query = Faculty::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        
        $faculties = $query->paginate(10)->withQueryString();
        
        return Inertia::render('Faculties/Index', [
            'faculties' => $faculties,
            'filters' => $request->only(['search']),
        ]);
    }
    
    public function sync()
    {
        try {
            Artisan::call('sync:sevima-faculties');
        } catch (\Exception $e) {
            return back()->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }
        
        return back()->with('success', 'Sinkronisasi data fakultas berhasil!');
    }
}
