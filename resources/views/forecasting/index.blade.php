@extends('layouts.app')
@section('title', 'AI Forecasting & Analytics')
@section('page-title', '🤖 AI Forecasting & Sales Predictions')

@section('content')
{{-- Tab Nav --}}
<ul class="nav nav-tabs mb-4">
  <li class="nav-item">
    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#salesTab">📈 Sales Forecast</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#productTab" onclick="loadProductForecast()">📦 Product Demand</button>
  </li>
  <li class="nav-item">
    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#stockTab" onclick="loadStockForecast()">⚠️ Stock Depletion</button>
  </li>
</ul>

<div class="tab-content">

  {{-- Sales Forecast Tab --}}
  <div class="tab-pane fade show active" id="salesTab">
    <div class="card mb-3">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Forecast Days</label>
            <select class="form-select" id="forecastDays">
              <option value="7">7 days</option>
              <option value="14">14 days</option>
              <option value="30" selected>30 days</option>
              <option value="60">60 days</option>
              <option value="90">90 days</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Historical Data</label>
            <select class="form-select" id="historyDays">
              <option value="30">30 days history</option>
              <option value="60">60 days history</option>
              <option value="90" selected>90 days history</option>
              <option value="180">180 days history</option>
            </select>
          </div>
          <div class="col-md-3">
            <button class="btn btn-primary w-100" onclick="loadSalesForecast()">
              <i class="fas fa-robot me-1"></i> Generate Forecast
            </button>
          </div>
        </div>
      </div>
    </div>

    <div id="salesMetrics" class="row g-3 mb-4 d-none">
      <div class="col-md-3">
        <div class="card bg-primary text-white text-center">
          <div class="card-body">
            <div class="fs-4 fw-bold" id="metTotalForecast">-</div>
            <div class="small">Total Forecast Revenue</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-info text-white text-center">
          <div class="card-body">
            <div class="fs-4 fw-bold" id="metAvgDaily">-</div>
            <div class="small">Avg Daily Revenue</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-center" id="trendCard">
          <div class="card-body">
            <div class="fs-4 fw-bold" id="metTrend">-</div>
            <div class="small">Trend</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card bg-secondary text-white text-center">
          <div class="card-body">
            <div class="fs-4 fw-bold" id="metAccuracy">-</div>
            <div class="small">Model Accuracy</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card" id="salesChartCard" style="display:none">
      <div class="card-header d-flex justify-content-between">
        <span>Revenue Forecast</span>
        <span class="text-muted small" id="genAt"></span>
      </div>
      <div class="card-body">
        <canvas id="salesChart" style="max-height:380px"></canvas>
      </div>
    </div>
  </div>

  {{-- Product Demand Tab --}}
  <div class="tab-pane fade" id="productTab">
    <div id="productForecastContainer">
      <div class="text-center text-muted py-5">Click the tab to load forecasts</div>
    </div>
  </div>

  {{-- Stock Depletion Tab --}}
  <div class="tab-pane fade" id="stockTab">
    <div id="stockForecastContainer">
      <div class="text-center text-muted py-5">Click the tab to load stock forecasts</div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
let salesChartObj = null;

async function apiFetch(url) {
  const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
  return res.json();
}

/* ── Sales Forecast ── */
async function loadSalesForecast() {
  const days    = document.getElementById('forecastDays').value;
  const history = document.getElementById('historyDays').value;

  const data = await apiFetch(`/api/forecast/sales?days=${days}&history=${history}`);
  if (data.error) { alert(data.error); return; }

  document.getElementById('salesMetrics').classList.remove('d-none');
  document.getElementById('salesChartCard').style.display = '';

  document.getElementById('metTotalForecast').textContent = fmt(data.total_forecast);
  document.getElementById('metAvgDaily').textContent      = fmt(data.avg_daily);
  document.getElementById('metAccuracy').textContent      = data.accuracy_pct + '%';
  document.getElementById('genAt').textContent            = 'Generated: ' + new Date(data.generated_at).toLocaleString();

  const trendMap = { growing: 'success', stable: 'warning', declining: 'danger' };
  const trendCard = document.getElementById('trendCard');
  const trendEl   = document.getElementById('metTrend');
  trendCard.className = `card text-white bg-${trendMap[data.trend] ?? 'secondary'} text-center`;
  trendEl.textContent = '↗ ' + (data.trend ?? '-').toUpperCase();

  // Chart
  const labels   = data.forecasts.map(f => f.date);
  const forecast = data.forecasts.map(f => f.forecast);
  const lower    = data.forecasts.map(f => f.lower_ci);
  const upper    = data.forecasts.map(f => f.upper_ci);

  if (salesChartObj) salesChartObj.destroy();
  salesChartObj = new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: 'Forecast', data: forecast, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.1)', tension: .4, fill: false },
        { label: 'Upper CI', data: upper,    borderColor: 'rgba(34,197,94,.4)', borderDash: [5,5], tension: .4, fill: false, pointRadius: 0 },
        { label: 'Lower CI', data: lower,    borderColor: 'rgba(239,68,68,.4)', borderDash: [5,5], tension: .4, fill: '-1', backgroundColor: 'rgba(59,130,246,.05)', pointRadius: 0 },
      ],
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'top' } },
      scales: { y: { beginAtZero: false } },
    },
  });
}

/* ── Product Demand ── */
async function loadProductForecast() {
  const container = document.getElementById('productForecastContainer');
  if (container.dataset.loaded) return;
  container.innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div></div>';

  const data = await apiFetch('/api/forecast/products?top=20&history=60');
  container.dataset.loaded = '1';

  if (!data.products?.length) { container.innerHTML = '<p class="text-muted">No data</p>'; return; }

  const rows = data.products.map(p => `
    <tr>
      <td>${esc(p.product_name)}</td>
      <td>${p.avg_daily_qty}</td>
      <td class="fw-bold">${p.forecast_30_days}</td>
      <td>${p.current_stock}</td>
      <td>
        <span class="badge bg-${p.days_stock_left <= 3 ? 'danger' : p.days_stock_left <= 7 ? 'warning' : 'success'}">
          ${p.days_stock_left} days
        </span>
      </td>
      <td class="${p.velocity_pct > 0 ? 'text-success' : p.velocity_pct < 0 ? 'text-danger' : ''}">
        ${p.velocity_pct > 0 ? '↑' : p.velocity_pct < 0 ? '↓' : '–'} ${Math.abs(p.velocity_pct)}%
      </td>
      <td>${fmt(p.total_revenue)}</td>
      ${p.needs_reorder ? '<td><span class="badge bg-danger">⚠ Reorder</span></td>' : '<td></td>'}
    </tr>`).join('');

  container.innerHTML = `
    <div class="card">
      <div class="card-header">Product Demand Forecast (30 days) <small class="text-muted">Generated: ${new Date(data.generated_at).toLocaleString()}</small></div>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead><tr><th>Product</th><th>Avg Daily</th><th>Forecast 30d</th><th>Stock</th><th>Days Left</th><th>Velocity</th><th>Revenue</th><th></th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    </div>`;
}

/* ── Stock Depletion ── */
async function loadStockForecast() {
  const container = document.getElementById('stockForecastContainer');
  if (container.dataset.loaded) return;
  container.innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div></div>';

  const data = await apiFetch('/api/forecast/stock?history=30');
  container.dataset.loaded = '1';

  if (!data.alerts?.length) {
    container.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>All stock levels are healthy (30+ days coverage)</div>';
    return;
  }

  const urgencyColor = { critical: 'danger', high: 'warning', medium: 'info', low: 'secondary' };

  const rows = data.alerts.map(a => `
    <tr class="${a.urgency === 'critical' ? 'table-danger' : a.urgency === 'high' ? 'table-warning' : ''}">
      <td><span class="badge bg-${urgencyColor[a.urgency]}">${a.urgency.toUpperCase()}</span></td>
      <td>${esc(a.product_name)}</td>
      <td>${a.current_stock}</td>
      <td>${a.daily_rate}/day</td>
      <td class="fw-bold">${a.days_remaining} days</td>
      <td>${a.depleted_on}</td>
      <td>~${a.reorder_qty} units</td>
    </tr>`).join('');

  container.innerHTML = `
    <div class="row g-3 mb-3">
      <div class="col-md-4"><div class="card text-center border-danger"><div class="card-body"><div class="fs-4 fw-bold text-danger">${data.critical}</div><div class="small">Critical (&lt; 3 days)</div></div></div></div>
      <div class="col-md-4"><div class="card text-center border-warning"><div class="card-body"><div class="fs-4 fw-bold text-warning">${data.total_at_risk}</div><div class="small">Total At Risk (&lt; 30 days)</div></div></div></div>
    </div>
    <div class="card">
      <div class="card-header">Stock Depletion Forecast</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead><tr><th>Urgency</th><th>Product</th><th>Stock</th><th>Rate</th><th>Days Left</th><th>Depleted On</th><th>Suggest Order</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    </div>`;
}

function fmt(n) { return parseFloat(n || 0).toLocaleString(undefined, {minimumFractionDigits:2,maximumFractionDigits:2}); }
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// Auto-load sales on page visit
loadSalesForecast();
</script>
@endpush
