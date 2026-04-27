<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * List all users (for role assignment dropdowns and reports).
     */
    public function all()
    {
        $users = User::select('id', 'username', 'full_name', 'role', 'is_active')
            ->with('roles:id,name')
            ->get();

        return response()->json(['users' => $users]);
    }

    /**
     * Create a new user.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'username'  => 'required|string|max:50|unique:users,username|regex:/^\S+$/',
            'full_name' => 'required|string|max:100',
            'password'  => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role'      => 'required|in:admin,cashier,warehouse',
            'language'  => 'nullable|in:ar,en',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::create([
            'username'  => $data['username'],
            'full_name' => $data['full_name'],
            'password'  => Hash::make($data['password']),
            'role'      => $data['role'],
            'language'  => $data['language'] ?? 'ar',
            'is_active' => $data['is_active'] ?? true,
        ]);

        // Sync role via Spatie permissions
        $user->syncRoles([$data['role']]);

        return response()->json(['success' => true, 'user' => $user->load('roles')], 201);
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'full_name' => 'sometimes|required|string|max:100',
            'role'      => 'sometimes|required|in:admin,cashier,warehouse',
            'language'  => 'nullable|in:ar,en',
            'is_active' => 'nullable|boolean',
            'password'  => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return response()->json(['success' => true, 'user' => $user->fresh()->load('roles')]);
    }

    /**
     * Deactivate (soft-disable) a user. Never hard-delete to preserve audit trail.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => __('pos.cannot_delete_self')], 422);
        }

        $user->update(['is_active' => false]);

        return response()->json(['success' => true]);
    }
}
