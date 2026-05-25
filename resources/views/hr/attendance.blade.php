@extends('layouts.app')
@section('title', __('pos.hr_attendance'))
@section('page-title', '📋 ' . __('pos.hr_attendance'))

@section('content')

{{-- HR Module Summary --}}
@include('hr._summary')

{{-- Filter Bar --}}
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body py-2">
    <div class="row g-2 align-items-center">
      <div class="col-auto">
        <label class="form-label mb-0 fw-semibold small">{{ __('pos.work_date') }}</label>
      </div>
      <div class="col-auto">
        <input type="date" id="filterDate" class="form-control form-control-sm"
               value="{{ date('Y-m-d') }}">
      </div>
      <div class="col-auto">
        <select id="filterBranch" class="form-select form-select-sm" style="min-width:160px">
          <option value="">{{ app()->getLocale()==='ar' ? 'كل الفروع' : 'All Branches' }}</option>
          @php /** @var \App\Models\Branch[] $branches */ @endphp
          @foreach($branches ?? [] as $b)
            <option value="{{ $b->id }}">{{ $b->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-auto">
        <select id="filterStatus" class="form-select form-select-sm">
          <option value="">{{ app()->getLocale()==='ar' ? 'كل الحالات' : 'All Statuses' }}</option>
          <option value="present">{{ __('pos.present') }}</option>
          <option value="absent">{{ __('pos.absent') }}</option>
          <option value="late">{{ __('pos.late') }}</option>
          <option value="half_day">{{ __('pos.half_day') }}</option>
          <option value="remote">{{ __('pos.remote') }}</option>
        </select>
      </div>
      <div class="col-auto ms-auto">
        <button class="btn btn-sm btn-outline-secondary" id="btnRefresh">
          <i class="fas fa-rotate-right me-1"></i>{{ app()->getLocale()==='ar' ? 'تحديث' : 'Refresh' }}
        </button>
        <button class="btn btn-sm btn-outline-primary" id="btnExport">
          <i class="fas fa-file-export me-1"></i>{{ app()->getLocale()==='ar' ? 'تصدير' : 'Export' }}
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Summary Badges --}}
<div class="row g-3 mb-4">
  @foreach([
    ['id'=>'cntPresent','icon'=>'fas fa-user-check','color'=>'success','label'=> app()->getLocale()==='ar' ? 'حاضرون' : 'Present'],
    ['id'=>'cntAbsent', 'icon'=>'fas fa-user-xmark','color'=>'danger', 'label'=> app()->getLocale()==='ar' ? 'غائبون'  : 'Absent'],
    ['id'=>'cntLate',   'icon'=>'fas fa-user-clock','color'=>'warning','label'=> app()->getLocale()==='ar' ? 'متأخرون' : 'Late'],
    ['id'=>'cntRemote', 'icon'=>'fas fa-laptop',    'color'=>'info',   'label'=> app()->getLocale()==='ar' ? 'عن بُعد' : 'Remote'],
  ] as $card)
  <div class="col-sm-6 col-md-3">
    <div class="card border-0 shadow-sm text-center">
      <div class="card-body py-3">
        <div class="fs-2 fw-bold text-{{ $card['color'] }}" id="{{ $card['id'] }}">–</div>
        <div class="text-muted small"><i class="{{ $card['icon'] }} me-1"></i>{{ $card['label'] }}</div>
      </div>
    </div>
  </div>
  @endforeach
</div>

{{-- Attendance Table --}}
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>{{ app()->getLocale()==='ar' ? 'الموظف' : 'Employee' }}</th>
            <th>{{ __('pos.work_date') }}</th>
            <th>{{ __('pos.check_in') }}</th>
            <th>{{ __('pos.check_out') }}</th>
            <th>{{ __('pos.late_minutes') }}</th>
            <th>{{ app()->getLocale()==='ar' ? 'ساعات العمل' : 'Hours' }}</th>
            <th>{{ app()->getLocale()==='ar' ? 'الحالة' : 'Status' }}</th>
            <th>{{ app()->getLocale()==='ar' ? 'ملاحظة' : 'Notes' }}</th>
          </tr>
        </thead>
        <tbody id="attendanceBody">
          <tr><td colspan="8" class="text-center py-4">
            <div class="spinner-border spinner-border-sm me-2 text-primary"></div>
            {{ app()->getLocale()==='ar' ? 'جاري التحميل…' : 'Loading…' }}
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script @nonce>
const statusColors = { present:'success', absent:'danger', late:'warning', half_day:'info', remote:'primary' };
const statusLabels = {
  present:  '{{ __('pos.present') }}',
  absent:   '{{ __('pos.absent') }}',
  late:     '{{ __('pos.late') }}',
  half_day: '{{ __('pos.half_day') }}',
  remote:   '{{ __('pos.remote') }}',
};

async function loadAttendance() {
  const date   = document.getElementById('filterDate').value;
  const branch = document.getElementById('filterBranch').value;
  const status = document.getElementById('filterStatus').value;

  const params = new URLSearchParams({ date });
  if (branch) params.append('branch_id', branch);
  if (status) params.append('status', status);

  const res  = await fetch('/api/hr/attendance?' + params, { headers: { 'Accept': 'application/json' } });
  const data = await res.json();
  const rows = data.records ?? [];

  // Summary
  const cnt = { present: 0, absent: 0, late: 0, remote: 0 };
  rows.forEach(r => { if (cnt[r.status] !== undefined) cnt[r.status]++; });
  document.getElementById('cntPresent').textContent = cnt.present;
  document.getElementById('cntAbsent').textContent  = cnt.absent;
  document.getElementById('cntLate').textContent    = cnt.late;
  document.getElementById('cntRemote').textContent  = cnt.remote;

  const tbody = document.getElementById('attendanceBody');
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">
      <i class="fas fa-calendar-xmark fa-2x d-block mb-2 opacity-25"></i>
      {{ app()->getLocale()==='ar' ? 'لا توجد سجلات' : 'No attendance records' }}
    </td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map(r => {
    const col   = statusColors[r.status] ?? 'secondary';
    const label = statusLabels[r.status] ?? r.status;
    const late  = r.late_minutes > 0 ? `<span class="text-danger">${r.late_minutes} min</span>` : '–';
    const hours = r.hours_worked ? `${r.hours_worked}h` : '–';
    const empUrl = r.user_id ? `/hr/employees?open=${r.user_id}` : '/hr/employees';
    return `<tr>
      <td>
        <div class="d-flex align-items-center gap-2">
          <div class="avatar-circle bg-${col} bg-opacity-15 text-${col}">${esc(r.user?.name?.[0] ?? '?')}</div>
          <div>
            <a href="${empUrl}" class="fw-semibold text-dark text-decoration-none">${esc(r.user?.name ?? '–')}</a>
            <div class="text-muted small">${esc(r.branch?.name ?? '')}</div>
          </div>
        </div>
      </td>
      <td>${r.work_date ?? '–'}</td>
      <td>${r.check_in ? fmtTime(r.check_in) : '–'}</td>
      <td>${r.check_out ? fmtTime(r.check_out) : '–'}</td>
      <td>${late}</td>
      <td>${hours}</td>
      <td><span class="badge bg-${col}">${label}</span></td>
      <td class="text-muted small">${esc(r.notes ?? '')}</td>
    </tr>`;
  }).join('');
}

function fmtTime(iso) {
  return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── Event listeners (no inline handlers) ─────────────────────────────────
document.getElementById('filterDate').addEventListener('change',   loadAttendance);
document.getElementById('filterBranch').addEventListener('change', loadAttendance);
document.getElementById('filterStatus').addEventListener('change', loadAttendance);
document.getElementById('btnRefresh').addEventListener('click',    loadAttendance);
document.getElementById('btnExport').addEventListener('click', () => {
  const date = document.getElementById('filterDate').value;
  window.open('/api/hr/attendance/export?date=' + date, '_blank');
});

loadAttendance();
</script>

<style>
.avatar-circle {
  width: 34px; height: 34px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: .8rem; flex-shrink: 0;
}
</style>
@endpush
