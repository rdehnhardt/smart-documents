<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'is_admin' => $user->is_admin,
                'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'created_at' => $user->created_at,
            ]);

        return Inertia::render('settings/users/index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('settings/users/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_admin' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
        ]);

        event(new Registered($user));

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully. A verification email has been sent.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('settings/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'is_admin' => $user->is_admin,
                'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'created_at' => $user->created_at,
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'is_admin' => ['boolean'],
        ]);

        $emailChanged = $user->email !== $validated['email'];

        // Prevent removing own admin status
        $isAdmin = $validated['is_admin'] ?? $user->is_admin;
        if ($user->id === $request->user()->id && ! $isAdmin) {
            return back()->with('error', 'You cannot remove your own admin status.');
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
            'is_admin' => $isAdmin,
        ]);

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
