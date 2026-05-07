<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use App\Traits\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponse, AuditLog;

    public function all()
    {
        $this->authorize('viewAny', User::class);
        return $this->success(['users' => UserResource::collection(User::with('roles')->get())]);
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);
        $data = $request->validated();
        $user = User::create([
            'username'  => $data['username'],
            'full_name' => $data['full_name'],
            'password'  => Hash::make($data['password']),
            'role'      => $data['role'],
            'is_active' => $data['is_active'] ?? true,
        ]);
        $user->syncRoles([$data['role']]);
        $this->audit('user.created', User::class, (int) $user->id, ['username' => $user->username]);
        return $this->success(['user' => new UserResource($user->load('roles'))], '', 201);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $updateData = collect($request->validated())->except(['password', 'password_confirm'])->toArray();
        if (!empty($request->validated()['password'])) {
            $updateData['password'] = Hash::make($request->validated()['password']);
        }
        $user->update($updateData);
        $user->syncRoles([$request->validated()['role']]);
        $this->audit('user.updated', User::class, (int) $user->id);
        return $this->success(['user' => new UserResource($user->fresh()->load('roles'))]);
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        if ((int) $user->id === (int) Auth::id()) {
            return $this->error(__('pos.cannot_delete_self'), 403);
        }
        if ($user->hasRole('admin') && User::role('admin')->where('is_active', true)->count() <= 1) {
            return $this->error(__('pos.cannot_delete_last_admin'), 403);
        }
        $user->delete();
        $this->audit('user.deleted', User::class, (int) $user->id, ['username' => (string) $user->username]);
        return $this->success();
    }

    public function toggleActive(User $user)
    {
        $this->authorize('toggleActive', $user);
        $user->update(['is_active' => !$user->is_active]);
        $this->audit('user.toggled', User::class, (int) $user->id, ['is_active' => $user->is_active]);
        return $this->success(['is_active' => $user->is_active]);
    }
}
