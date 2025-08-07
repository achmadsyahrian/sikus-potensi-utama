<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;
use App\Models\StudentDetail;
use App\Services\Sevima\AuthService as SevimaAuthService;
use App\Services\Sevima\MahasiswaService;
use Exception;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function index()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        try {
            $sevimaData = SevimaAuthService::attemptLogin($credentials['email'], $credentials['password']);

            if ($sevimaData) {
                $sevimaUserData = $sevimaData['attributes'];
                $sevimaUserId = $sevimaUserData['user_id'];
                $sevimaRoles = $sevimaUserData['role'];

                $user = User::firstOrCreate(
                    ['sevima_user_id' => $sevimaUserId],
                    [
                        'name' => Str::title($sevimaUserData['nama']),
                        'email' => $sevimaUserData['email'],
                        'auth_provider' => 'sevima',
                        'password' => null,
                    ]
                );

                $isMahasiswa = collect($sevimaRoles)->contains('id_role', 'mhs');
                if ($isMahasiswa) {
                    $mahasiswaRole = Role::where('slug', 'mahasiswa')->first();
                    if ($mahasiswaRole) {
                        $user->roles()->syncWithoutDetaching([$mahasiswaRole->id]);
                    }
                    $studentLoginData = collect($sevimaRoles)->where('id_role', 'mhs')->first();
                    $nim = $studentLoginData['nim'];

                    $sevimaData = MahasiswaService::fetchStudentsByNim($nim);
                    (new MahasiswaService())->syncStudent($user, $sevimaData);
                }

                Auth::login($user, $request->remember);
                $request->session()->regenerate();
                return redirect()->route('dashboard');
            }
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'email' => 'Akun anda tidak dapat ditemukan di sistem kami.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
        ]);
    }




    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
