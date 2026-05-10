<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RoleService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    use ApiResponse;

    public function __construct(private RoleService $roleService) {}

    public function getRoles()
    {
        return $this->success(['roles' => $this->roleService->allWithPermissions()]);
    }

    public function getPermissions()
    {
        return $this->success(['permissions' => $this->roleService->allPermissions()]);
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100|unique:roles,name',
            'guard_name' => 'nullable|string|in:web,api',
        ]);
        $role = $this->roleService->create($request->only(['name', 'guard_name']));
        return $this->success(['role' => $role, 'message' => __('pos.role_created')], '', 201);
    }

    public function updateRole(Request $request, Role $role)
    {
        $request->validate(['name' => "required|string|max:100|unique:roles,name,{$role->id}"]);
        try {
            $updated = $this->roleService->update($role, $request->only('name'));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 403);
        }
        return $this->success(message: __('pos.role_updated'));
    }

    public function destroyRole(Role $role)
    {
        try {
            $this->roleService->delete($role);
        } catch (\Exception $e) {
            $code = str_contains($e->getMessage(), 'protected') ? 403 : 422;
            return $this->error($e->getMessage(), $code);
        }
        return $this->success(message: __('pos.role_deleted'));
    }

    public function syncPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);
        try {
            $this->roleService->syncPermissions($role, $request->input('permissions', []));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 403);
        }
        return $this->success(message: __('pos.permissions_updated'));
    }

    public function getUserRoles(User $user)
    {
        return $this->success([
            'roles'     => $user->getRoleNames(),
            'all_roles' => $this->roleService->allWithPermissions(),
        ]);
    }

    public function assignUserRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|exists:roles,name']);
        $user->syncRoles([$request->string('role')->toString()]);
        return $this->success(message: __('pos.role_assigned'));
    }
}
