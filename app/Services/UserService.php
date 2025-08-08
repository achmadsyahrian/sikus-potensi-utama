<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserService
{
    public function createUser(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make('12345678'),
            'auth_provider' => 'local',
        ]);

        $user->roles()->attach($data['roles']);

        return $user;
    }

    public function updateUser(User $user, array $data)
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user->roles()->sync($data['roles']);

        return $user;
    }

    public function deleteUser(User $user)
    {
        if ($user->auth_provider === 'sevima') {
            return false;
        }

        $user->delete();

        return true;
    }

    public function resetUserPassword(User $user)
    {
        if ($user->auth_provider === 'sevima') {
            return false;
        }

        $user->update([
            'password' => Hash::make('12345678')
        ]);

        return true;
    }
}
