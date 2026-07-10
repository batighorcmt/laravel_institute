@extends('layouts.admin')
@section('title', 'লাইভ ডিভাইস মনিটর')

@push('styles')
<style>
body { background: #0f0f1a; }
.monitor-wrapper { min-height: 100vh; background: #0f0f1a; color: #e2e8f0; }

/* Header */
.monitor-header {
    background: linear-gradient(135deg,#1e1e2e 0%,#16213e 100%);
    border-bottom: 1px solid rgba(125,211,252,.15);
    padding: 1rem 1.5rem;
}

/* Device status cards */
.device-monitor-card {
    background: linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);
    border-radius: 16px;
    padding: 1.25rem;
    border: 1px solid rgba(255,255,255,.07);
    transition: all .3s;
    position: relative;
    overflow: hidden;
}
.device-monitor-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
}
.device-monitor-card.online::before  { background: linear-gradient(90deg,#34d399,#10b981); }
.device-monitor-card.offline::before { background: linear-gradient(90deg,#f87171,#ef4444); }
.device-monitor-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,.4); }

.pulse-dot {
    width: 12px; height: 12px; border-radius: 50%;
    display: inline-block;
    position: relative;
}
.pulse-dot.online  { background: #34d399; }
.pulse-dot.offline { background: #f87171; }
.pulse-dot.online::after {
    content: '';
    position: absolute;
    top: -4px; left: -4px;
    width: 20px; height: 20px;
    border-radius: 50%;
    background: rgba(52,211,153,.3);
    animation: ping 1.5s cubic-bezier(0,0,.2,1) infinite;
}
@keyframes ping {
    75%, 100% { transform: scale(2); opacity: 0; }
}

/* Stats bar */
.stat-pill {
    background: rgba(125,211,252,.08);
    border: 1px solid rgba(125,211,252,.2);
    border-radius: 50px;
    padding: .4rem 1rem;
    font-size: .85rem;
}

/* Live punch feed */
.punch-feed {
    background: #0d0d1a;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.06);
    max-height: 420px;
    overflow-y: auto;
}
.punch-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .6rem 1rem;
    border-bottom: 1px solid rgba(255,255,255,.04);
    animation: slideIn .3s ease;
}
.punch-item:last-child { border-bottom: none; }
@keyframes slideIn {
    from { opacity: 0; transform: translateX(-10px); }
    to   { opacity: 1; transform: translateX(0); }
}
.punch-type-in  { color: #34d399; }
.punch-type-out { color: #fb923c; }

/* Chart */
.chart-container {
    background: #0d0d1a;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.06);
    padding: 1rem;
}

/* Last updated badge */
.last-update-badge {
    font-size: .75rem;
    color: #94a3b8;
    background: rgba(148,163,184,.08);
    border-radius: 50px;
    padding: .2rem .6rem;
}

/* Alert box */
.unassigned-alert {
    background: linear-gradient(135deg,rgba(245,158,11,.12),rgba(245,158,11,.04));
    border: 1px solid rgba(245,158,11,.3);
    border-radius: 12px;
}
</style>
@endpush

@section('content')
<div class="monitor-wrapper">

    {{-- Monitor Header --}}
    <div class="monitor-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div>
                <h5 class="mb-0 fw-bold text-info">
                    <span class="pulse-dot online me-2 align-middle"></span>
                    লাইভ বায়োমেট্রিক মনিটর
                </h5>
                <small class="text-muted">{{ $school->name }}</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="last-update-badge" id="lastUpdate">আপডেট হচ্ছে...</span>
            <a href="{{ route('principal.institute.biometric.profiles.unassigned', $school) }}"
               class="btn btn-sm btn-outline-warning position-relative">
                <i class="fas fa-question-circle me-1"></i>অজানা প্রোফাইল
                @if($unassignedCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    {{ $unassignedCount }}
                </span>
                @endif
            </a>
            <a href="{{ route('principal.institute.biometric.dashboard', $school) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>ড্যাশবোর্ড
            </a>
        </div>
    </div>

    <div class="container-fluid py-3">

        {{-- Top Stats --}}
        <div class="d-flex flex-wrap gap-3 mb-4">
            <div class="stat-pill border-{{ $agentIsOnline ? 'success' : 'danger' }}">
                <i class="fas fa-desktop text-{{ $agentIsOnline ? 'success' : 'danger' }} me-1"></i>
                লোকাল এজেন্ট:
                <strong class="text-white" id="statAgent">
                    {{ $agentIsOnline ? 'অনলাইন' : 'অফলাইন' }}
                </strong>
                <small class="ms-1 text-muted" id="statAgentLastSeen">({{ $agentLastSeen }})</small>
                <div class="small text-muted mt-1" id="statAgentOnlineDuration">
                    একটানা অনলাইন: {{ $agentOnlineDuration }}
                </div>
            </div>
            <div class="stat-pill">
                <i class="fas fa-calendar-day text-info me-1"></i>
                আজকের পাঞ্চ: <strong id="statToday" class="text-white">{{ $todayTotal }}</strong>
            </div>
            <div class="stat-pill">
                <i class="fas fa-circle text-success me-1"></i>
                অনলাইন: <strong class="text-white" id="statOnline">{{ $devices->where('status','online')->count() }}</strong>
            </div>
            <div class="stat-pill">
                <i class="fas fa-circle text-danger me-1"></i>
                অফলাইন: <strong class="text-white" id="statOffline">{{ $devices->where('status','offline')->count() }}</strong>
            </div>
            <div class="stat-pill">
                <i class="fas fa-clock text-warning me-1"></i>
                শেষ পাঞ্চ: <strong class="text-white" id="statLastPunch">—</strong>
            </div>
        </div>

        <div class="row g-4">

            {{-- Left: Device Cards --}}
            <div class="col-lg-4">
                <h6 class="text-muted text-uppercase small mb-3 fw-semibold letter-spacing-1">
                    <i class="fas fa-microchip me-1"></i> ডিভাইস স্ট্যাটাস
                </h6>
                <div id="deviceCards">
                    @forelse($devices as $device)
                    <div class="device-monitor-card {{ $device->status }} mb-3" id="device-{{ $device->id }}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="pulse-dot {{ $device->status }} me-2 align-middle"></span>
                                <strong>{{ $device->device_name }}</strong>
                            </div>
                            <span class="badge {{ $device->status === 'online' ? 'bg-success' : 'bg-danger' }} device-badge">
                                {{ strtoupper($device->status) }}
                            </span>
                        </div>
                        <div class="small text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $device->location ?? 'লোকেশন নেই' }}
                        </div>
                        <div class="small text-muted mt-1">
                            <i class="fas fa-network-wired me-1"></i>{{ $device->ip_address ?? 'N/A' }}
                        </div>
                        @if($device->last_seen)
                        <div class="small text-muted mt-1 device-lastseen" data-id="{{ $device->id }}">
                            <i class="fas fa-clock me-1"></i>{{ $device->last_seen->diffForHumans() }}
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-microchip fa-3x mb-3 d-block"></i>
                        কোনো ডিভাইস যোগ করা হয়নি।
                    </div>
                    @endforelse
                </div>

                {{-- Unassigned alert --}}
                @if($unassignedCount > 0)
                <div class="unassigned-alert p-3 mt-3">
                    <div class="fw-semibold text-warning mb-1">
                        <i class="fas fa-exclamation-triangle me-1"></i>{{ $unassignedCount }}টি অজানা প্রোফাইল
                    </div>
                    <div class="small text-muted mb-2">ডিভাইস থেকে আসা ফিঙ্গারপ্রিন্ট এখনো কারো সাথে লিংক হয়নি।</div>
                    <a href="{{ route('principal.institute.biometric.profiles.unassigned', $school) }}"
                       class="btn btn-warning btn-sm">লিংক করুন</a>
                </div>
                @endif
            </div>

            {{-- Center: Hourly Chart --}}
            <div class="col-lg-4">
                <h6 class="text-muted text-uppercase small mb-3 fw-semibold">
                    <i class="fas fa-chart-bar me-1"></i> আজকের ঘণ্টাওয়ারি পাঞ্চ
                </h6>
                <div class="chart-container">
                    <canvas id="hourlyChart" height="250"></canvas>
                </div>
            </div>

            {{-- Right: Live Punch Feed --}}
            <div class="col-lg-4">
                <h6 class="text-muted text-uppercase small mb-3 fw-semibold">
                    <i class="fas fa-stream me-1"></i> রিয়েল-টাইম পাঞ্চ ফিড
                    <span class="badge bg-success ms-1 blink-badge">● LIVE</span>
                </h6>
                <div class="punch-feed" id="punchFeed">
                    @forelse($recentPunches as $punch)
                    <div class="punch-item">
                        <div class="text-center" style="min-width:36px">
                            <span class="{{ $punch->punch_type === 'check_in' ? 'punch-type-in' : 'punch-type-out' }} fs-5">
                                {{ $punch->punch_type === 'check_in' ? '↓' : '↑' }}
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-white small">
                                {{ $punch->student?->student_name_en ?? 'ID #'.$punch->biometric_id }}
                            </div>
                            <div class="text-muted" style="font-size:.75rem">
                                {{ $punch->device?->device_name ?? 'Unknown' }}
                            </div>
                        </div>
                        <div class="text-end text-muted" style="font-size:.75rem">
                            {{ \Carbon\Carbon::parse($punch->punch_time)->format('h:i A') }}
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">আজ কোনো পাঞ্চ নেই</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const statusUrl = @json(route('principal.institute.biometric.monitor.status', $school));
const SCHOOL_TIMEZONE = 'Asia/Dhaka';

// ── Hourly Chart ─────────────────────────────────────────────────────────────
const hours = Array.from({length: 24}, (_, i) => i + ':00');
const counts = Array.from({length: 24}, (_, i) => {{ json_encode($hourlyStats) }}[i] ?? 0);

const ctx = document.getElementById('hourlyChart').getContext('2d');
const hourlyChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: hours,
        datasets: [{
            label: 'পাঞ্চ সংখ্যা',
            data: counts,
            backgroundColor: 'rgba(125,211,252,.4)',
            borderColor: 'rgba(125,211,252,1)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#64748b', font:{size:9} }, grid: { color: 'rgba(255,255,255,.05)' } },
            y: { ticks: { color: '#64748b', stepSize: 1 }, grid: { color: 'rgba(255,255,255,.05)' }, beginAtZero: true }
        }
    }
});

// ── Live status polling ───────────────────────────────────────────────────────
async function pollStatus() {
    try {
        const resp = await fetch(statusUrl);
        if (!resp.ok) return;
        const data = await resp.json();

        // Update stats
        document.getElementById('statToday').textContent    = data.today_total;
        document.getElementById('statLastPunch').textContent = data.last_punch;

        // Update agent status
        const agentEl = document.getElementById('statAgent');
        const agentLastSeenEl = document.getElementById('statAgentLastSeen');
        const agentDurationEl = document.getElementById('statAgentOnlineDuration');
        const agentContainer = agentEl.parentElement;
        
        agentEl.textContent = data.agent_is_online ? 'অনলাইন' : 'অফলাইন';
        agentLastSeenEl.textContent = '(' + data.agent_last_seen + ')';
        agentDurationEl.textContent = 'একটানা অনলাইন: ' + data.agent_online_duration;
        
        if (data.agent_is_online) {
            agentContainer.classList.remove('border-danger');
            agentContainer.classList.add('border-success');
            agentContainer.querySelector('i').classList.replace('text-danger', 'text-success');
        } else {
            agentContainer.classList.remove('border-success');
            agentContainer.classList.add('border-danger');
            agentContainer.querySelector('i').classList.replace('text-success', 'text-danger');
        }

        let online = 0, offline = 0;
        data.devices.forEach(dev => {
            const card = document.getElementById('device-' + dev.id);
            if (!card) return;

            const prevStatus = card.classList.contains('online') ? 'online' : 'offline';
            if (prevStatus !== dev.status) {
                card.classList.remove('online', 'offline');
                card.classList.add(dev.status);
                // Update badge
                const badge = card.querySelector('.device-badge');
                badge.className = 'badge device-badge ' + (dev.status === 'online' ? 'bg-success' : 'bg-danger');
                badge.textContent = dev.status.toUpperCase();
                // Update dot
                const dot = card.querySelector('.pulse-dot');
                dot.classList.remove('online','offline');
                dot.classList.add(dev.status);
            }
            // Update last seen
            const lsEl = card.querySelector('.device-lastseen');
            if (lsEl) lsEl.innerHTML = `<i class="fas fa-clock me-1"></i>${dev.last_seen}`;

            if (dev.status === 'online') online++; else offline++;
        });

        document.getElementById('statOnline').textContent  = online;
        document.getElementById('statOffline').textContent = offline;

        const now = new Date().toLocaleTimeString('bn-BD');
        document.getElementById('lastUpdate').textContent = 'আপডেট: ' + now;
    } catch (e) {}
}

// Poll every 15 seconds
pollStatus();
setInterval(pollStatus, 15000);
</script>
<style>
.blink-badge { animation: blink 1.2s step-start infinite; }
@keyframes blink { 50% { opacity: 0; } }
</style>
@endpush
@endsection
