<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,mail',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create($data);

        Auth::login($user);
        //regenerate() -> genera un session ID tras el login del user
        $request->session()->regenerate();

        return response()->json($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        //compara los mail registrados, si hay coincidencia valida la pass ->::attempt()
        // al fallar -> code 422
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => "Las credenciales no son correctas"
            ]);
        }

        $request->session()->regenerate();
        return response()->json(Auth::user());
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }
}
