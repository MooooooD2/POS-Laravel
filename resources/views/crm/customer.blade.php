@extends('layouts.app')
@section('title', 'CRM — ' . $customer->name)
@section('page-title', '👤 ' . $customer->name)

@section('content')
<div class="row g-4">
  {{-- Customer Profile --}}
  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title">{{ $customer->name }}</h5>
        <p class="text-muted mb-1"><i class="fas fa-phone me-1"></i>{{ $customer->phone ?? 'N/A' }}</p>
        <p class="text-muted mb-1"><i class="fas fa-envelope me-1"></i>{{ $customer->email ?? 'N/A' }}</p>
        <hr>
        @php
          $colors = ['lead'=>'secondary','prospect'=>'info','customer'=>'primary','loyal'=>'success','at_risk'=>'warning','churned'=>'danger'];
          $stage  = $customer->lifecycle_stage ?? 'customer';
        @endphp
        <div class="d-flex justify-content-between mb-2">
          <span>Lifecycle Stage</span>
          <span class="badge bg-{{ $colors[$stage] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$stage)) }}</span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span>Lifetime Value</span>
          <strong>{{ number_format($customer->lifetime_value ?? 0, 2) }}</strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span>Total Purchases</span>
          <strong>{{ $customer->purchase_count ?? 0 }}</strong>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span>Loyalty Points</span>
          <strong>{{ $customer->loyalty_points ?? 0 }}</strong>
        </div>
        <div class="d-flex justify-content-between">
          <span>Cashback Balance</span>
          <strong class="text-success">{{ number_format($customer->cashback_balance ?? 0, 2) }}</strong>
        </div>
      </div>
    </div>

    {{-- Recent Invoices --}}
    <div class="card">
      <div class="card-header">Recent Invoices</div>
      <div class="list-group list-group-flush">
        @foreach($invoices as $inv)
        <div class="list-group-item d-flex justify-content-between py-2">
          <span class="small">#{{ $inv->invoice_number }}</span>
          <span class="small fw-bold">{{ number_format($inv->final_total, 2) }}</span>
          <span class="small text-muted">{{ $inv->created_at?->format('M d') }}</span>
        </div>
        @endforeach
        @if($invoices->isEmpty())
        <div class="list-group-item text-muted text-center small py-3">No invoices</div>
        @endif
      </div>
    </div>
  </div>

  {{-- Activity Log --}}
  <div class="col-md-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span>Activity Log</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addActivityModal">
          <i class="fas fa-plus me-1"></i> Log Activity
        </button>
      </div>
      <div class="card-body p-0" style="max-height:600px;overflow-y:auto">
        <div id="activitiesContainer">
          @foreach($activities as $act)
          <div class="border-bottom p-3" id="act-{{ $act->id }}">
            <div class="d-flex justify-content-between mb-1">
              <span class="fw-semibold">{{ $act->type_icon }} {{ ucfirst($act->type) }}{{ $act->subject ? ' — ' . $act->subject : '' }}</span>
              <small class="text-muted">{{ $act->created_at->diffForHumans() }}</small>
            </div>
            @if($act->notes)
            <p class="text-muted small mb-1">{{ $act->notes }}</p>
            @endif
            <div class="d-flex gap-2 align-items-center">
              @php $oc = ['positive'=>'success','negative'=>'danger','neutral'=>'secondary','pending'=>'warning']; @endphp
              <span class="badge bg-{{ $oc[$act->outcome] ?? 'secondary' }}">{{ ucfirst($act->outcome) }}</span>
              @if($act->scheduled_at)
              <small class="text-muted">📅 {{ $act->scheduled_at->format('M d, H:i') }}</small>
              @endif
              <small class="text-muted ms-auto">by {{ $act->user?->full_name ?? 'System' }}</small>
            </div>
          </div>
          @endforeach
          @if($activities->isEmpty())
          <div class="text-center text-muted py-5">No activities logged yet</div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Add Activity Modal --}}
<div class="modal fade" id="addActivityModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Log Activity</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Type</label>
          <select class="form-select" id="actType">
            <option value="call">📞 Phone Call</option>
            <option value="email">📧 Email</option>
            <option value="whatsapp">💬 WhatsApp</option>
            <option value="visit">🏪 Visit</option>
            <option value="note" selected>📝 Note</option>
            <option value="complaint">⚠️ Complaint</option>
            <option value="follow_up">🔔 Follow-up</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Subject</label>
          <input type="text" class="form-control" id="actSubject" placeholder="Brief summary">
        </div>
        <div class="mb-3">
          <label class="form-label">Notes</label>
          <textarea class="form-control" id="actNotes" rows="3" placeholder="Detailed notes…"></textarea>
        </div>
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label">Outcome</label>
            <select class="form-select" id="actOutcome">
              <option value="neutral">Neutral</option>
              <option value="positive">Positive</option>
              <option value="negative">Negative</option>
              <option value="pending">Pending Follow-up</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Schedule Follow-up</label>
            <input type="datetime-local" class="form-control" id="actScheduled">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="saveActivityBtn">Save Activity</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

document.getElementById('saveActivityBtn').addEventListener('click', async () => {
  const payload = {
    customer_id:  {{ $customer->id }},
    type:         document.getElementById('actType').value,
    subject:      document.getElementById('actSubject').value.trim() || null,
    notes:        document.getElementById('actNotes').value.trim() || null,
    outcome:      document.getElementById('actOutcome').value,
    scheduled_at: document.getElementById('actScheduled').value || null,
  };

  const btn = document.getElementById('saveActivityBtn');
  btn.disabled = true;

  const res  = await fetch('/api/crm/activities', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify(payload),
  });
  const data = await res.json();
  btn.disabled = false;

  if (res.ok) {
    bootstrap.Modal.getInstance(document.getElementById('addActivityModal')).hide();
    location.reload();
  } else {
    alert(data.message ?? 'Error saving activity');
  }
});
</script>
@endpush
