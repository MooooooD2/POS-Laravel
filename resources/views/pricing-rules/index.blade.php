@extends('layouts.app')
@section('title', 'Dynamic Pricing Rules')
@section('page-title', '💰 Dynamic Pricing & Happy Hour Rules')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <p class="text-muted mb-0">Configure time-based, bulk, and smart pricing rules.</p>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ruleModal" onclick="openCreate()">
    <i class="fas fa-plus me-1"></i> New Rule
  </button>
</div>

<div id="happyHourBanner" class="alert alert-warning d-none mb-3">
  🎉 <strong>Happy Hour is Active!</strong> Special prices are applied right now.
</div>

<div class="table-responsive">
  <table class="table table-hover">
    <thead>
      <tr>
        <th>Name</th><th>Type</th><th>Discount</th>
        <th>Schedule</th><th>Priority</th>
        <th>Status</th><th>Active Now</th><th></th>
      </tr>
    </thead>
    <tbody id="rulesTable"><tr><td colspan="8" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr></tbody>
  </table>
</div>

{{-- Rule Modal --}}
<div class="modal fade" id="ruleModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ruleModalTitle">New Pricing Rule</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ruleId">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Rule Name</label>
            <input type="text" class="form-control" id="ruleName" placeholder="e.g. Happy Hour 3–5 PM">
          </div>
          <div class="col-md-4">
            <label class="form-label">Priority (1–100)</label>
            <input type="number" class="form-control" id="rulePriority" value="10" min="1" max="100">
          </div>
          <div class="col-md-4">
            <label class="form-label">Rule Type</label>
            <select class="form-select" id="ruleType" onchange="toggleTypeFields()">
              <option value="happy_hour">⏰ Happy Hour</option>
              <option value="bulk_discount">📦 Bulk Discount</option>
              <option value="day_of_week">📅 Day of Week</option>
              <option value="category">🏷 Category</option>
              <option value="flat_price">💲 Flat Price</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Discount Type</label>
            <select class="form-select" id="discountType">
              <option value="percentage">Percentage (%)</option>
              <option value="fixed_amount">Fixed Amount</option>
              <option value="new_price">New Price</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Discount Value</label>
            <input type="number" class="form-control" id="discountValue" step="0.01" min="0" placeholder="e.g. 15">
          </div>

          {{-- Time fields (happy_hour) --}}
          <div class="col-md-6 time-fields">
            <label class="form-label">Start Time</label>
            <input type="time" class="form-control" id="timeStart">
          </div>
          <div class="col-md-6 time-fields">
            <label class="form-label">End Time</label>
            <input type="time" class="form-control" id="timeEnd">
          </div>

          {{-- Days of week --}}
          <div class="col-12 dow-fields">
            <label class="form-label">Days of Week</label>
            <div class="d-flex gap-2 flex-wrap" id="dowCheckboxes">
              @foreach(['Mon'=>1,'Tue'=>2,'Wed'=>3,'Thu'=>4,'Fri'=>5,'Sat'=>6,'Sun'=>7] as $label => $val)
              <div class="form-check form-check-inline">
                <input class="form-check-input dow-cb" type="checkbox" value="{{ $val }}" id="dow{{ $val }}">
                <label class="form-check-label" for="dow{{ $val }}">{{ $label }}</label>
              </div>
              @endforeach
            </div>
          </div>

          {{-- Date range --}}
          <div class="col-md-6">
            <label class="form-label">Valid From (optional)</label>
            <input type="date" class="form-control" id="validFrom">
          </div>
          <div class="col-md-6">
            <label class="form-label">Valid Until (optional)</label>
            <input type="date" class="form-control" id="validUntil">
          </div>

          {{-- Bulk --}}
          <div class="col-md-4 bulk-fields">
            <label class="form-label">Min Quantity</label>
            <input type="number" class="form-control" id="minQty" step="0.01" min="0" placeholder="e.g. 5">
          </div>

          <div class="col-md-4">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" id="ruleActive" checked>
              <label class="form-check-label">Active</label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" id="ruleStackable">
              <label class="form-check-label">Stackable (combine rules)</label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Description (optional)</label>
            <input type="text" class="form-control" id="ruleDesc" placeholder="Internal description…">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveRuleBtn">Save Rule</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const API  = '/api/pricing-rules';

let editId = null;

function toggleTypeFields() {
  const type = document.getElementById('ruleType').value;
  document.querySelectorAll('.time-fields').forEach(el => el.style.display = type === 'happy_hour' ? '' : 'none');
  document.querySelectorAll('.dow-fields').forEach(el  => el.style.display = ['happy_hour','day_of_week'].includes(type) ? '' : 'none');
  document.querySelectorAll('.bulk-fields').forEach(el => el.style.display = type === 'bulk_discount' ? '' : 'none');
}

function openCreate() {
  editId = null;
  document.getElementById('ruleModalTitle').textContent = 'New Pricing Rule';
  document.getElementById('ruleId').value = '';
  ['ruleName','discountValue','ruleDesc','timeStart','timeEnd','validFrom','validUntil','minQty'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('ruleType').value = 'happy_hour';
  document.getElementById('discountType').value = 'percentage';
  document.getElementById('rulePriority').value = '10';
  document.getElementById('ruleActive').checked = true;
  document.getElementById('ruleStackable').checked = false;
  document.querySelectorAll('.dow-cb').forEach(cb => cb.checked = false);
  toggleTypeFields();
}

function openEdit(rule) {
  editId = rule.id;
  document.getElementById('ruleModalTitle').textContent = 'Edit Rule';
  document.getElementById('ruleName').value     = rule.name;
  document.getElementById('ruleType').value     = rule.rule_type;
  document.getElementById('discountType').value = rule.discount_type;
  document.getElementById('discountValue').value= rule.discount_value;
  document.getElementById('rulePriority').value = rule.priority;
  document.getElementById('ruleActive').checked = !!rule.is_active;
  document.getElementById('ruleStackable').checked = !!rule.stackable;
  document.getElementById('ruleDesc').value     = rule.description ?? '';
  document.getElementById('timeStart').value    = rule.time_start ?? '';
  document.getElementById('timeEnd').value      = rule.time_end ?? '';
  document.getElementById('validFrom').value    = rule.valid_from ?? '';
  document.getElementById('validUntil').value   = rule.valid_until ?? '';
  document.getElementById('minQty').value       = rule.min_quantity ?? '';
  document.querySelectorAll('.dow-cb').forEach(cb => {
    cb.checked = (rule.days_of_week ?? []).includes(parseInt(cb.value));
  });
  toggleTypeFields();
  new bootstrap.Modal(document.getElementById('ruleModal')).show();
}

document.getElementById('saveRuleBtn').addEventListener('click', async () => {
  const dows = [...document.querySelectorAll('.dow-cb:checked')].map(cb => parseInt(cb.value));
  const payload = {
    name:           document.getElementById('ruleName').value.trim(),
    rule_type:      document.getElementById('ruleType').value,
    discount_type:  document.getElementById('discountType').value,
    discount_value: parseFloat(document.getElementById('discountValue').value) || 0,
    priority:       parseInt(document.getElementById('rulePriority').value) || 10,
    is_active:      document.getElementById('ruleActive').checked,
    stackable:      document.getElementById('ruleStackable').checked,
    description:    document.getElementById('ruleDesc').value.trim() || null,
    time_start:     document.getElementById('timeStart').value || null,
    time_end:       document.getElementById('timeEnd').value || null,
    days_of_week:   dows.length ? dows : null,
    valid_from:     document.getElementById('validFrom').value || null,
    valid_until:    document.getElementById('validUntil').value || null,
    min_quantity:   parseFloat(document.getElementById('minQty').value) || null,
  };

  const url    = editId ? `${API}/${editId}` : API;
  const method = editId ? 'PUT' : 'POST';

  const res = await fetch(url, {
    method,
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify(payload),
  });
  if (res.ok) {
    bootstrap.Modal.getInstance(document.getElementById('ruleModal'))?.hide();
    loadRules();
  } else {
    const d = await res.json();
    alert(d.message ?? 'Error saving rule');
  }
});

async function toggleRule(id) {
  await fetch(`${API}/${id}/toggle`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': CSRF } });
  loadRules();
}

async function deleteRule(id) {
  if (!confirm('Delete this rule?')) return;
  await fetch(`${API}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
  loadRules();
}

async function loadRules() {
  const data = await fetch(API, { headers: { 'Accept': 'application/json' } }).then(r => r.json());

  // Check happy hour
  const happyHourActive = data.some(r => r.rule_type === 'happy_hour' && r.is_currently_active);
  document.getElementById('happyHourBanner').classList.toggle('d-none', !happyHourActive);

  const typeIcon = { happy_hour:'⏰', bulk_discount:'📦', day_of_week:'📅', category:'🏷', flat_price:'💲', loyalty_tier:'⭐' };

  document.getElementById('rulesTable').innerHTML = data.map(r => `
    <tr>
      <td><strong>${esc(r.name)}</strong>${r.description ? `<br><small class="text-muted">${esc(r.description)}</small>` : ''}</td>
      <td>${typeIcon[r.rule_type] ?? ''} ${r.rule_type.replace('_',' ')}</td>
      <td>${r.discount_value}${r.discount_type === 'percentage' ? '%' : r.discount_type === 'fixed_amount' ? ' off' : ' (new)'}</td>
      <td><small>${r.time_start ? r.time_start + ' – ' + r.time_end : '—'} ${r.days_of_week?.length ? '<br>Days: ' + r.days_of_week.join(',') : ''}</small></td>
      <td>${r.priority}</td>
      <td><span class="badge bg-${r.is_active ? 'success' : 'secondary'}">${r.is_active ? 'Active' : 'Inactive'}</span></td>
      <td>${r.is_currently_active ? '<span class="badge bg-warning text-dark">✓ Now</span>' : '–'}</td>
      <td>
        <button class="btn btn-sm btn-outline-secondary" onclick='openEdit(${JSON.stringify(r)})'>Edit</button>
        <button class="btn btn-sm btn-outline-${r.is_active ? 'warning' : 'success'}" onclick="toggleRule(${r.id})">${r.is_active ? 'Disable' : 'Enable'}</button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteRule(${r.id})">Del</button>
      </td>
    </tr>`).join('') || '<tr><td colspan="8" class="text-center text-muted py-3">No rules yet</td></tr>';
}

function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

loadRules();
</script>
@endpush
