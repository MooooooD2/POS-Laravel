@extends('layouts.app')
@section('title', 'Active Devices')
@section('page-title', '🔐 Active Devices & Sessions')

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Your Active Sessions</span>
    <button class="btn btn-sm btn-outline-danger" onclick="revokeAll()">
      <i class="fas fa-sign-out-alt me-1"></i> Sign Out All Other Devices
    </button>
  </div>
  <div class="card-body p-0">
    <div class="list-group list-group-flush">
      @foreach($sessions as $session)
      <div class="list-group-item d-flex justify-content-between align-items-center" id="sess-{{ $session->id }}">
        <div class="d-flex align-items-center gap-3">
          <div class="fs-2">
            @if($session->device_type === 'mobile') 📱
            @elseif($session->device_type === 'tablet') 📲
            @else 💻
            @endif
          </div>
          <div>
            <div class="fw-semibold">
              {{ $session->device_name ?? 'Unknown Device' }}
              @if($session->is_current)
                <span class="badge bg-success ms-1">Current</span>
              @endif
            </div>
            <div class="small text-muted">
              IP: {{ $session->ip_address ?? 'Unknown' }}
              · Last active: {{ $session->last_active_at?->diffForHumans() ?? 'Unknown' }}
            </div>
          </div>
        </div>
        @if(!$session->is_current)
        <button class="btn btn-sm btn-outline-danger" onclick="revokeSession({{ $session->id }})">
          <i class="fas fa-times"></i> Revoke
        </button>
        @endif
      </div>
      @endforeach
      @if($sessions->isEmpty())
      <div class="list-group-item text-center text-muted py-4">No active sessions found</div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function revokeSession(id) {
  if (!confirm('Revoke this session?')) return;
  const res = await fetch(`/device-sessions/${id}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
  });
  if (res.ok) {
    document.getElementById(`sess-${id}`)?.remove();
  }
}

async function revokeAll() {
  if (!confirm('Sign out all other devices?')) return;
  const res = await fetch('/device-sessions', {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
  });
  if (res.ok) location.reload();
}
</script>
@endpush
