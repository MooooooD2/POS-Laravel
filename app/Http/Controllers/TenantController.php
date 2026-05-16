<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    use ApiResponse;

    private function guardMasterTenant(): void
    {
        $masterId = config('tenancy.master_tenant');
        if ($masterId && tenancy()->tenant?->id !== $masterId) {
            abort(403, __('pos.master_tenant_only'));
        }
    }

    public function index()
    {
        $this->guardMasterTenant();
        // Tenant model always uses the central connection – safe to call here
        $tenants = Tenant::orderBy('created_at')->get();
        return view('tenants.index', compact('tenants'));
    }

    public function store(Request $request)
    {
        $this->guardMasterTenant();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|alpha_dash|unique:tenants,code',
            'plan' => 'nullable|string|in:basic,pro,enterprise',
        ]);

        $tenant = Tenant::create([
            'name' => $data['name'],
            'code' => Str::lower($data['code']),
            'plan' => $data['plan'] ?? 'basic',
            'is_active' => true,
        ]);
        // CreateDatabase + MigrateDatabase listeners fire automatically via TenancyServiceProvider

        return $this->success(['tenant' => $tenant], __('pos.tenant_created'));
    }

    public function update(Request $request, string $id)
    {
        $this->guardMasterTenant();

        $tenant = Tenant::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'plan' => 'nullable|string|in:basic,pro,enterprise',
        ]);

        $tenant->update($data);
        return $this->success(['tenant' => $tenant->fresh()], __('pos.tenant_updated'));
    }

    public function toggle(string $id)
    {
        $this->guardMasterTenant();

        $tenant = Tenant::findOrFail($id);
        $tenant->update(['is_active' => !$tenant->is_active]);
        return $this->success([], $tenant->is_active ? __('pos.tenant_activated') : __('pos.tenant_deactivated'));
    }

    public function destroy(string $id)
    {
        $this->guardMasterTenant();

        $tenant = Tenant::findOrFail($id);

        // Prevent deleting the master tenant itself
        if ($id === config('tenancy.master_tenant')) {
            return $this->error(__('pos.cannot_delete_master_tenant'), 422);
        }

        $tenant->delete(); // DeleteDatabase listener fires automatically

        return $this->success([], __('pos.tenant_deleted'));
    }

    public function seed(string $id)
    {
        $this->guardMasterTenant();

        $tenant = Tenant::findOrFail($id);

        tenancy()->initialize($tenant);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder', '--force' => true]);
        tenancy()->end();

        // Re-initialize the master tenant so the response continues normally
        $master = Tenant::find(config('tenancy.master_tenant'));
        if ($master)
            tenancy()->initialize($master);

        return $this->success([], __('pos.tenant_seeded'));
    }
}
