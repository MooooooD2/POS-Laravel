<?php
// app/Http/Controllers/RolePermissionController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionController extends Controller
{
    /**
     * Get all roles
     */
    public function getRoles()
    {
        $roles = Role::with('permissions')->get();
        return response()->json(['roles' => $roles]);
    }

    /**
     * Get all permissions
     */
    public function getPermissions()
    {
        $permissions = Permission::all();
        return response()->json(['permissions' => $permissions]);
    }

    /**
     * Create new role
     */
    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'guard_name' => 'nullable|string|default:web'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? 'web'
        ]);

        return response()->json(['success' => true, 'role' => $role, 'message' => __('pos.role_created')]);
    }

    /**
     * Update role
     */
    public function updateRole(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id
        ]);

        $role->update(['name' => $request->name]);

        return response()->json(['success' => true, 'message' => __('pos.role_updated')]);
    }

    /**
     * Delete role
     */
    public function destroyRole(Role $role)
    {
        $role->delete();
        return response()->json(['success' => true, 'message' => __('pos.role_deleted')]);
    }

    /**
     * Sync permissions to role
     */
    public function syncPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'array'
        ]);

        $role->syncPermissions($request->permissions);

        return response()->json(['success' => true, 'message' => __('pos.permissions_updated')]);
    }

    /**
     * Get user roles
     */
    public function getUserRoles(User $user)
    {
        return response()->json([
            'roles' => $user->getRoleNames(),
            'all_roles' => Role::all()
        ]);
    }

    /**
     * Assign role to user
     */
    public function assignUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        $user->syncRoles([$request->role]);

        return response()->json(['success' => true, 'message' => __('pos.role_assigned')]);
    }
}