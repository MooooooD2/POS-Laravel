<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    use ApiResponse;

    private const PROTECTED_ROLES = ['admin'];

    public function getRoles()
    {
        return $this->success(['roles' => Role::with('permissions')->get()]);
    }

    public function getPermissions()
    {
        return $this->success(['permissions' => Permission::all()]);
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100|unique:roles,name',
            'guard_name' => 'nullable|string|in:web,api',
        ]);

        $role = Role::create([
            'name'       => $request->string('name')->toString(),
            'guard_name' => $request->string('guard_name')->toString() ?: 'web',
        ]);

        return $this->success(['role' => $role, 'message' => __('pos.role_created')], '', 201);
    }

    public function updateRole(Request $request, Role $role)
    {
        if (\in_array($role->name, self::PROTECTED_ROLES, true)) {
            return $this->error(__('pos.role_protected'), 403);
        }

        $request->validate([
            'name' => "required|string|max:100|unique:roles,name,{$role->id}",
        ]);

        $role->update(['name' => $request->string('name')->toString()]);
        return $this->success(message: __('pos.role_updated'));
    }

    public function destroyRole(Role $role)
    {
        if (\in_array($role->name, self::PROTECTED_ROLES, true)) {
            return $this->error(__('pos.cannot_delete_protected_role'), 403);
        }

        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            return $this->error(__('pos.role_has_users', ['count' => $usersCount]), 422);
        }

        $role->delete();
        return $this->success(message: __('pos.role_deleted'));
    }

    public function syncPermissions(Request $request, Role $role)
    {
        if (\in_array($role->name, self::PROTECTED_ROLES, true)) {
            return $this->error(__('pos.role_protected'), 403);
        }

        $request->validate([
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($request->input('permissions', []));
        return $this->success(message: __('pos.permissions_updated'));
    }

    public function getUserRoles(User $user)
    {
        return $this->success([
            'roles'     => $user->getRoleNames(),
            'all_roles' => Role::all(),
        ]);
    }

    public function assignUserRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|exists:roles,name']);
        $user->syncRoles([$request->string('role')->toString()]);
        return $this->success(message: __('pos.role_assigned'));
    }
}
