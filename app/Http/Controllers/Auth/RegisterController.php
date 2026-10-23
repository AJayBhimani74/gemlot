<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'in:'.User::ROLE_BUYER.','.User::ROLE_SELLER],
            'company_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['required', 'string', 'size:2'],
        ]);

        $data['country'] = strtoupper($data['country']);

        // Sellers are reviewed by an admin before they can list stones.
        $data['is_approved'] = $data['role'] === User::ROLE_BUYER;

        $user = User::create($data);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
