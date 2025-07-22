<?php

namespace App\Services;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Регистрация пользователя
     *
     * @param array $userData
     * @return array
     * @throws ValidationException
     */
    public function registerUser(array $userData): array
    {
        $existingUser = User::where('email', $userData['email'])->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'email' => 'Пользователь уже существует'
            ]);
        }

        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);

        $token = JWTAuth::fromUser($user);

        return [
            'token' => $token
        ];
    }

    /**
     * Авторизация пользователя
     *
     * @param string $email
     * @param string $password
     * @return array
     * @throws ValidationException
     */
    public function loginUser(string $email, string $password): array
    {
        $credentials = [
            'email' => $email,
            'password' => $password
        ];

        $token = auth('api')->attempt($credentials);

        if (!$token) {
            throw ValidationException::withMessages([
                'email' => 'Неверный email или пароль'
            ]);
        }

        return [
            'token' => $token
        ];
    }

    /**
     * Выход из системы
     *
     * @return bool
     */
    public function logoutUser(): bool
    {
        auth('api')->logout();
        return true;
    }
}
