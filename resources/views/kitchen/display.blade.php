<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ __('pos.kitchen_display') ?? 'Kitchen Display System' }}</title>
<style>
  :root {
    --kds-bg: #0f172a;
    --card-pending: #78350f;
    --card-preparing: #1e3a5f;
    --card-ready: #14532d;
    --urgent: #dc2626;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: var(--kds-bg);
    color: #f1f5f9;
    font-family: 'Segoe UI', Arial, sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
  }
  /* ─── Header ─── */
  .kds-header {
    background: #1e293b;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #334155;
  }
  .kds-header h1 { font-size: 1.4rem; font-weight: 700; color: #f59e0b; }
  .kds-clock { font-size: 1.6rem; font-weight: 700; color: #38bdf8; font-variant-numeric: tabular-nums; }
  .kds-stats { display: flex; gap: 16px; }
  .stat-pill {
    padding: 4px 14px; border-radius: 20px; font-size: .85rem; font-weight: 600;
  }
  .stat-pending  { background: #78350f; }
  .stat-preparing{ background: #1e3a5f; }
  .stat-ready    { background: #14532d; }

  /* ─── Grid ─── */
  .kds-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 14px;
    padding: 16px;
  }

  /* ─── Cards ─── */
  .order-card {
    border-radius: 12px;
    padding: 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    animation: fadeIn .3s ease;
    position: relative;
    border: 2px solid transparent;
    transition: border-color .3s;
  }
  .order-card.urgent { border-color: var(--urgent) !important; animation: urgentPulse 1.5s infinite; }
  .order-card.status-pending   { background: #451a03; border-color: #92400e; }
  .order-card.status-preparing { background: #0c1a2e; border-color: #1d4ed8; }
  .order-card.status-ready     { background: #052e16; border-color: #16a34a; }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .order-num { font-size: 1.4rem; font-weight: 800; color: #f59e0b; }
  .table-num { font-size: .85rem; background: #334155; padding: 2px 10px; border-radius: 20px; }
  .order-type { font-size: .75rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
  .elapsed {
    font-size: .75rem;
    padding: 2px 8px;
    border-radius: 10px;
    background: #1e293b;
  }
  .elapsed.urgent { background: var(--urgent); color: #fff; font-weight: 700; }

  .items-list { list-style: none; }
  .item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px 0;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    gap: 8px;
  }
  .item-row:last-child { border: none; }
  .item-qty  { font-weight: 700; font-size: 1.1rem; color: #fbbf24; min-width: 30px; }
  .item-name { flex: 1; font-size: .95rem; }
  .item-note { font-size: .75rem; color: #94a3b8; display: block; }
  .item-status { font-size: .7rem; padding: 2px 6px; border-radius: 4px; }
  .item-status.done { background: #14532d; }

  .card-notes { font-size: .8rem; color: #cbd5e1; background: #1e293b; padding: 6px 8px; border-radius: 6px; }

  .card-actions { display: flex; gap: 8px; margin-top: 4px; }
  .btn-kds {
    flex: 1;
    padding: 8px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: .85rem;
    cursor: pointer;
    transition: opacity .2s;
  }
  .btn-kds:hover { opacity: .85; }
  .btn-accept  { background: #2563eb; color: #fff; }
  .btn-ready   { background: #16a34a; color: #fff; }
  .btn-serve   { background: #9333ea; color: #fff; }
  .btn-cancel  { background: #dc2626; color: #fff; }

  /* ─── Empty state ─── */
  .kds-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 60vh;
    color: #475569;
    gap: 12px;
  }
  .kds-empty svg { width: 80px; height: 80px; opacity: .3; }
  .kds-empty h2 { font-size: 1.5rem; }

  /* ─── Animations ─── */
  @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes urgentPulse {
    0%, 100% { border-color: var(--urgent); box-shadow: 0 0 0 0 rgba(220,38,38,.4); }
    50%       { border-color: #fca5a5; box-shadow: 0 0 0 8px rgba(220,38,38,0); }
  }

  /* ─── Sound icon ─── */
  .sound-btn { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.2rem; }
</style>
</head>
<body>

<div class="kds-header">
  <h1>🍳 Kitchen Display</h1>
  <div class="kds-stats">
    <span class="stat-pill stat-pending" id="statPending">Pending: 0</span>
    <span class="stat-pill stat-preparing" id="statPreparing">Cooking: 0</span>
    <span class="stat-pill stat-ready" id="statReady">Ready: 0</span>
  </div>
  <div style="display:flex;align-items:center;gap:12px;">
    <button class="sound-btn" id="soundToggle" title="Toggle sound">🔔</button>
    <div class="kds-clock" id="clock">--:--:--</div>
  </div>
</div>

<div id="ordersGrid" class="kds-grid"></div>

<audio id="newOrderSound" preload="auto">
  <source src="data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAA..." type="audio/wav">
</audio>

<script>
const API    = '{{ route("api.kitchen.orders") }}';
const ACCEPT = (id) => `{{ url("api/kitchen") }}/${id}/accept`;
const READY  = (id) => `{{ url("api/kitchen") }}/${id}/ready`;
const SERVED = (id) => `{{ url("api/kitchen") }}/${id}/served`;
const CANCEL = (id) => `{{ url("api/kitchen") }}/${id}/cancel`;
const CSRF   = document.querySelector('meta[name="csrf-token"]').content;

let knownIds    = new Set();
let soundOn     = true;
let pollInterval;

/* ─── Clock ─── */
function updateClock() {
  document.getElementById('clock').textContent = new Date().toLocaleTimeString();
}
setInterval(updateClock, 1000);
updateClock();

/* ─── Sound toggle ─── */
document.getElementById('soundToggle').addEventListener('click', function () {
  soundOn = !soundOn;
  this.textContent = soundOn ? '🔔' : '🔕';
});

function playNewOrderSound() {
  if (!soundOn) return;
  try {
    const ctx  = new (window.AudioContext || window.webkitAudioContext)();
    const osc  = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.frequency.setValueAtTime(880, ctx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.3);
    gain.gain.setValueAtTime(0.5, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.5);
  } catch (e) {}
}

/* ─── API helpers ─── */
async function apiFetch(url, method = 'GET') {
  const res = await fetch(url, {
    method,
    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
  });
  return res.json();
}

/* ─── Render ─── */
function elapsedLabel(minutes, urgent) {
  const cls = urgent ? 'elapsed urgent' : 'elapsed';
  return `<span class="${cls}">${minutes}m</span>`;
}

function statusLabel(status) {
  const map = { pending: '⏳ Pending', preparing: '🔥 Cooking', ready: '✅ Ready' };
  return map[status] ?? status;
}

function renderOrder(o) {
  const urgent   = o.is_urgent_val;
  const elapsed  = o.elapsed_minutes_val ?? 0;
  const items    = (o.items ?? []).map(item => `
    <li class="item-row">
      <span class="item-qty">×${parseFloat(item.quantity)}</span>
      <span class="item-name">
        ${escHtml(item.product_name)}
        ${item.notes ? `<span class="item-note">⚠ ${escHtml(item.notes)}</span>` : ''}
      </span>
      ${item.status === 'done' ? '<span class="item-status done">Done</span>' : ''}
    </li>`).join('');

  const actions = (() => {
    if (o.status === 'pending')   return `<button class="btn-kds btn-accept" onclick="accept(${o.id})">✓ Accept</button><button class="btn-kds btn-cancel" onclick="cancelOrder(${o.id})">✕ Cancel</button>`;
    if (o.status === 'preparing') return `<button class="btn-kds btn-ready" onclick="markReady(${o.id})">🔔 Ready</button><button class="btn-kds btn-cancel" onclick="cancelOrder(${o.id})">✕ Cancel</button>`;
    if (o.status === 'ready')     return `<button class="btn-kds btn-serve" onclick="serve(${o.id})">🍽 Served</button>`;
    return '';
  })();

  return `
  <div class="order-card status-${o.status}${urgent ? ' urgent' : ''}" id="order-${o.id}" data-id="${o.id}">
    <div class="card-header">
      <span class="order-num">#${escHtml(o.order_number)}</span>
      ${o.table_number ? `<span class="table-num">Table ${escHtml(o.table_number)}</span>` : ''}
      ${elapsedLabel(elapsed, urgent)}
    </div>
    <div class="order-type">${o.order_type?.replace('_',' ').toUpperCase()} · ${statusLabel(o.status)}</div>
    <ul class="items-list">${items}</ul>
    ${o.notes ? `<div class="card-notes">📝 ${escHtml(o.notes)}</div>` : ''}
    <div class="card-actions">${actions}</div>
  </div>`;
}

function escHtml(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ─── Poll ─── */
async function poll() {
  try {
    const { orders, stats } = await apiFetch(API);

    // detect new orders
    orders.forEach(o => {
      if (!knownIds.has(o.id)) { playNewOrderSound(); }
      knownIds.add(o.id);
    });

    // update stats
    document.getElementById('statPending').textContent   = `Pending: ${stats.pending}`;
    document.getElementById('statPreparing').textContent = `Cooking: ${stats.preparing}`;
    document.getElementById('statReady').textContent     = `Ready: ${stats.ready}`;

    // render grid
    const grid = document.getElementById('ordersGrid');
    if (orders.length === 0) {
      grid.innerHTML = `
        <div class="kds-empty" style="grid-column:1/-1">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
          </svg>
          <h2>No active orders</h2>
          <p>Waiting for orders…</p>
        </div>`;
    } else {
      grid.innerHTML = orders.map(renderOrder).join('');
    }
  } catch (e) {
    console.error('KDS poll error', e);
  }
}

/* ─── Actions ─── */
async function accept(id)      { await apiFetch(ACCEPT(id), 'POST'); await poll(); }
async function markReady(id)   { await apiFetch(READY(id),  'POST'); await poll(); }
async function serve(id)       { await apiFetch(SERVED(id), 'POST'); await poll(); }
async function cancelOrder(id) { if (confirm('Cancel this order?')) { await apiFetch(CANCEL(id), 'POST'); await poll(); } }

/* ─── Start polling ─── */
poll();
pollInterval = setInterval(poll, 8000); // refresh every 8 seconds
</script>
</body>
</html>
