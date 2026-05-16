@extends('layouts.app')

@section('title', __('pos.tenant_management'))

@section('content')
<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-building me-2 text-primary"></i>{{ __('pos.tenant_management') }}</h4>
            <small class="text-muted">{{ __('pos.tenant_management_desc') }}</small>
        </div>
        <button class="btn btn-primary" onclick="openTenantModal()">
            <i class="fas fa-plus me-1"></i>{{ __('pos.new_tenant') }}
        </button>
    </div>

    {{-- Master-tenant notice --}}
    @php $masterId = config('tenancy.master_tenant'); @endphp
    @if(!$masterId)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-1"></i>
        {{ __('pos.master_tenant_not_set') }}
        <code class="ms-1">MASTER_TENANT_ID</code>
    </div>
    @endif

    {{-- Tenants Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tenantsTable">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('pos.tenant_name') }}</th>
                            <th>{{ __('pos.tenant_code') }}</th>
                            <th>{{ __('pos.plan') }}</th>
                            <th>{{ __('pos.status') }}</th>
                            <th>{{ __('pos.created_at') }}</th>
                            <th class="text-center">{{ __('pos.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $tenant)
                        <tr id="tenant-row-{{ $tenant->id }}">
                            <td class="fw-semibold">
                                {{ $tenant->name }}
                                @if($tenant->id === $masterId)
                                    <span class="badge bg-warning text-dark ms-1">Master</span>
                                @endif
                            </td>
                            <td><code>{{ $tenant->code }}</code></td>
                            <td>
                                <span class="badge bg-{{ $tenant->plan === 'enterprise' ? 'primary' : ($tenant->plan === 'pro' ? 'info' : 'secondary') }}">
                                    {{ ucfirst($tenant->plan) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $tenant->is_active ? 'success' : 'danger' }}" id="status-{{ $tenant->id }}">
                                    {{ $tenant->is_active ? __('pos.active') : __('pos.inactive') }}
                                </span>
                            </td>
                            <td>{{ $tenant->created_at?->format('Y-m-d') }}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary me-1"
                                    onclick="openTenantModal({{ json_encode(['id'=>$tenant->id,'name'=>$tenant->name,'plan'=>$tenant->plan]) }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-{{ $tenant->is_active ? 'warning' : 'success' }} me-1"
                                    onclick="toggleTenant('{{ $tenant->id }}')">
                                    <i class="fas fa-{{ $tenant->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info me-1"
                                    onclick="seedTenant('{{ $tenant->id }}', '{{ addslashes($tenant->name) }}')"
                                    title="{{ __('pos.seed_tenant') }}">
                                    <i class="fas fa-database"></i>
                                </button>
                                @if($tenant->id !== $masterId)
                                <button class="btn btn-sm btn-outline-danger"
                                    onclick="deleteTenant('{{ $tenant->id }}', '{{ addslashes($tenant->name) }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">{{ __('pos.no_tenants') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Tenant Modal --}}
<div class="modal fade" id="tenantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tenantModalTitle">{{ __('pos.new_tenant') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="tenantAlert" class="alert d-none"></div>
                <input type="hidden" id="tenantId">

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('pos.tenant_name') }} *</label>
                    <input type="text" class="form-control" id="tenantName" placeholder="{{ __('pos.tenant_name_placeholder') }}">
                </div>

                <div class="mb-3" id="tenantCodeGroup">
                    <label class="form-label fw-semibold">{{ __('pos.tenant_code') }} *</label>
                    <input type="text" class="form-control" id="tenantCode"
                        placeholder="{{ __('pos.tenant_code_placeholder') }}"
                        pattern="[a-z0-9_-]+" style="direction:ltr">
                    <div class="form-text">{{ __('pos.tenant_code_help') }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">{{ __('pos.plan') }}</label>
                    <select class="form-select" id="tenantPlan">
                        <option value="basic">Basic</option>
                        <option value="pro">Pro</option>
                        <option value="enterprise">Enterprise</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('pos.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="saveTenantBtn" onclick="saveTenant()">
                    <span id="saveTenantText">{{ __('pos.save') }}</span>
                    <span class="spinner-border spinner-border-sm d-none ms-1" id="saveTenantSpinner"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script @nonce>
const TENANTS_STORE_URL  = "{{ route('admin.tenants.store') }}";
const TENANTS_UPDATE_URL = "{{ url('admin/tenants') }}";
const TENANTS_TOGGLE_URL = "{{ url('admin/tenants') }}";
const TENANTS_DELETE_URL = "{{ url('admin/tenants') }}";
const TENANTS_SEED_URL   = "{{ url('admin/tenants') }}";

let tenantModal;
document.addEventListener('DOMContentLoaded', () => {
    tenantModal = new bootstrap.Modal(document.getElementById('tenantModal'));
});

function openTenantModal(tenant = null) {
    document.getElementById('tenantAlert').classList.add('d-none');
    document.getElementById('tenantId').value   = tenant?.id ?? '';
    document.getElementById('tenantName').value = tenant?.name ?? '';
    document.getElementById('tenantCode').value = '';
    document.getElementById('tenantPlan').value = tenant?.plan ?? 'basic';
    document.getElementById('tenantModalTitle').textContent =
        tenant ? "{{ __('pos.edit_tenant') }}" : "{{ __('pos.new_tenant') }}";

    // Code field is only relevant for creation
    document.getElementById('tenantCodeGroup').classList.toggle('d-none', !!tenant?.id);
    tenantModal.show();
    setTimeout(() => document.getElementById('tenantName').focus(), 300);
}

async function saveTenant() {
    const id   = document.getElementById('tenantId').value;
    const name = document.getElementById('tenantName').value.trim();
    const code = document.getElementById('tenantCode').value.trim().toLowerCase();
    const plan = document.getElementById('tenantPlan').value;

    if (!name || (!id && !code)) {
        showTenantAlert("{{ __('pos.fill_required_fields') }}", 'danger');
        return;
    }

    setBusy(true);
    try {
        const url    = id ? `${TENANTS_UPDATE_URL}/${id}` : TENANTS_STORE_URL;
        const method = id ? 'PUT' : 'POST';
        const res    = await apiCall(url, method, { name, code, plan });

        if (!res.success) { showTenantAlert(res.message, 'danger'); return; }

        tenantModal.hide();
        setTimeout(() => location.reload(), 300);
    } catch (e) {
        showTenantAlert("{{ __('pos.server_error') }}", 'danger');
    } finally {
        setBusy(false);
    }
}

async function toggleTenant(id) {
    const res = await apiCall(`${TENANTS_TOGGLE_URL}/${id}/toggle`, 'PATCH');
    if (res.success) location.reload();
    else alert(res.message);
}

async function seedTenant(id, name) {
    if (!confirm(`{{ __('pos.seed_tenant_confirm') }} "${name}"?`)) return;
    const res = await apiCall(`${TENANTS_SEED_URL}/${id}/seed`, 'POST');
    alert(res.message || (res.success ? "Done" : "Error"));
}

async function deleteTenant(id, name) {
    if (!confirm(`{{ __('pos.delete_tenant_confirm') }} "${name}"?\n{{ __('pos.delete_tenant_warning') }}`)) return;
    const res = await apiCall(`${TENANTS_DELETE_URL}/${id}`, 'DELETE');
    if (res.success) {
        document.getElementById(`tenant-row-${id}`)?.remove();
    } else {
        alert(res.message);
    }
}

function showTenantAlert(msg, type) {
    const el = document.getElementById('tenantAlert');
    el.className = `alert alert-${type}`;
    el.textContent = msg;
    el.classList.remove('d-none');
}

function setBusy(busy) {
    document.getElementById('saveTenantBtn').disabled = busy;
    document.getElementById('saveTenantSpinner').classList.toggle('d-none', !busy);
}
</script>
@endpush
@endsection
