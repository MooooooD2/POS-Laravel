<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function getRoles()
    {
        return response()->json(['roles' => Role::with('permissions')->get()]);
    }

    public function getPermissions()
    {
        return response()->json(['permissions' => Permission::all()]);
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name',
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        return response()->json(['success' => true, 'role' => $role, 'message' => __('pos.role_created')], 201);
    }

    public function updateRole(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name,' . $role->id,
        ]);

        $role->update(['name' => $data['name']]);

        return response()->json(['success' => true, 'message' => __('pos.role_updated')]);
    }

    public function destroyRole(Role $role)
    {
        // Prevent deleting system roles
        if (in_array($role->name, ['admin', 'cashier', 'warehouse'])) {
            return response()->json([
                'success' => false,
                'message' => __('pos.cannot_delete_system_role'),
            ], 422);
        }

        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => __('pos.role_has_users'),
            ], 422);
        }

        $role->delete();

        return response()->json(['success' => true, 'message' => __('pos.role_deleted')]);
    }

    public function syncPermissions(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions'   => 'present|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->syncPermissions($data['permissions']);

        return response()->json(['success' => true, 'message' => __('pos.permissions_updated')]);
    }

    public function getUserRoles(User $user)
    {
        return response()->json([
            'roles'     => $user->getRoleNames(),
            'all_roles' => Role::all(),
        ]);
    }

    public function assignUserRole(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->syncRoles([$data['role']]);
        // Keep the legacy role column in sync
        $user->update(['role' => $data['role']]);

        return response()->json(['success' => true, 'message' => __('pos.role_assigned')]);
    }
}
