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
use App\Services\Sevima\LecturerService;
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
            'remember' => ['nullable', 'boolean']
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->remember)) {
            $request->session()->regenerate();
            $user = Auth::user();
            if ($user->roles->count() > 1) {
                return redirect()->route('role-selection.index');
            }
            return redirect()->intended(route('dashboard', absolute: false));
        }

        try {
            $sevimaData = SevimaAuthService::attemptLogin($credentials['email'], $credentials['password']);
            if ($sevimaData) {
                $sevimaUserData = $sevimaData['attributes'];
                $sevimaUserId = $sevimaUserData['user_id'];
                $sevimaRoles = $sevimaUserData['role'];

                if (empty($sevimaUserData['email']) || empty($sevimaUserData['nama'])) {
                    throw ValidationException::withMessages([
                        'email' => 'Data dari sistem eksternal tidak lengkap. Silakan coba lagi atau hubungi administrator.'
                    ]);
                }

                $user = User::firstOrCreate(
                    ['sevima_user_id' => $sevimaUserId],
                    [
                        'name' => Str::title($sevimaUserData['nama']),
                        'email' => $sevimaUserData['email'],
                        'auth_provider' => 'sevima',
                        'password' => null,
                    ]
                );

                $isDosen = collect($sevimaRoles)->contains('id_role', 'dosen');
                if ($isDosen) {
                    $dosenRole = Role::where('slug', 'dosen')->first();
                    if ($dosenRole) {
                        $user->roles()->syncWithoutDetaching([$dosenRole->id]);
                    }
                    $dosenLoginData = collect($sevimaRoles)->where('id_role', 'dosen')->first();
                    $id = $dosenLoginData['id_pegawai'];

                    $sevimaData = LecturerService::fetchLecturerById($id);
                    (new LecturerService())->syncLecturer($user, $sevimaData);
                }

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

                $isPegawai = collect($sevimaRoles)->whereNotIn('id_role', ['dosen', 'mhs', 'ortu'])->isNotEmpty();
                if ($isPegawai) {
                    $pegawaiRole = Role::where('slug', 'pegawai')->first();
                    if ($pegawaiRole) {
                        $user->roles()->syncWithoutDetaching([$pegawaiRole->id]);
                    }
                }

                Auth::login($user, $request->remember);
                $request->session()->regenerate();
                if ($user->roles->count() > 1) {
                    return redirect()->route('role-selection.index');
                }
                return redirect()->intended(route('dashboard', absolute: false));
            }
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'email' => 'Akun anda tidak dapat ditemukan di sistem kami.'
            ]);
        }

        throw ValidationException::withMessages([
            'email' => 'Kredensial yang diberikan tidak cocok dengan data kami.'
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
