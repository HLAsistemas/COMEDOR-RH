<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->orderBy('name')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $username = $request->input('username');
        if (is_string($username)) {
            $request->merge(['username' => strtolower(trim($username))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('comedor_rh_users', 'username'),
            ],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('comedor_rh_users', 'email')],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'is_admin' => ['required', 'integer', Rule::in([1, 2, 3])],
        ]);

        User::query()->create($data + [
            'is_active' => true,
        ]);

        return redirect()
            ->route('usuarios.index')
            ->with('status', 'El usuario se creó correctamente.');
    }
}
