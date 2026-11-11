@extends('erp.layouts.app')
@section('title', 'KPI & Performance')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Employee KPI & Performance</h1>
        <p class="ks-page-sub">Auto-calculated metrics from task data — {{ $period }}.</p>
    </div>
    <div class="ks-page-actions">
        <form method="POST" action="{{ route('erp.kpi.recalculate') }}" class="flex items-center gap-2">
            @csrf
            <input type="month" name="period" value="{{ $period }}" class="ks-form-input" style="width:150px;">
            <button type="submit" class="ks-btn ks-btn-primary">Recalculate</button>
        </form>
        <a href="{{ route('erp.kpi.definitions') }}" class="ks-btn ks-btn-light">Definitions</a>
    </div>
</div>

{{-- Charts Row --}}
<div class="ks-grid-3">
    <div class="ks-card">
        <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Completion Rate</p></div>
        <div class="ks-card-body"><div class="ks-h-56"><canvas id="completionChart"></canvas></div></div>
    </div>
    <div class="ks-card">
        <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Score Distribution</p></div>
        <div class="ks-card-body"><div class="ks-h-56"><canvas id="scoreChart"></canvas></div></div>
    </div>
    <div class="ks-card">
        <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Tasks vs Completion</p></div>
        <div class="ks-card-body"><div class="ks-h-56"><canvas id="taskChart"></canvas></div></div>
    </div>
</div>

{{-- KPI Results Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">KPI Results — {{ $period }}</p>
        <span class="ks-badge ks-badge-blue">{{ $results->total() }} results</span>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>KPI</th>
                        <th style="text-align:right;">Actual</th>
                        <th style="text-align:right;">Target</th>
                        <th style="text-align:right;">Score</th>
                        <th style="text-align:center;">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $r)
                    <tr>
                        <td class="ks-strong">{{ $r->user->name ?? 'N/A' }}</td>
                        <td>{{ $r->kpiDefinition->name ?? 'N/A' }}</td>
                        <td style="text-align:right;font-weight:600;">{{ $r->actual_value ?? '—' }} <span style="font-size:11px;color:var(--gray-400);">{{ $r->kpiDefinition->unit ?? '' }}</span></td>
                        <td style="text-align:right;">{{ $r->target_value ?? '—' }}</td>
                        <td style="text-align:right;">
                            @if($r->score)
                                <span class="ks-badge ks-badge-blue">{{ $r->score }}%</span>
                            @else
                                —
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($r->score)
                            <div style="width:80px;height:6px;background:var(--gray-100);border-radius:3px;overflow:hidden;display:inline-block;">
                                <div style="height:100%;width:{{ min(100, $r->score) }}%;background:linear-gradient(90deg,var(--role-grad-1),var(--role-grad-3));border-radius:3px;"></div>
                            </div>
                            @else
                            —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:48px;">No KPI results for {{ $period }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $results->links() }}</div>
</div>

@if($results->isEmpty())
    <div class="ks-card" style="text-align:center;padding:48px;">
        <i class="ti ti-chart-bar" style="font-size:40px;color:var(--gray-300);"></i>
        <p style="font-size:14px;color:var(--gray-400);margin:16px 0 0;">No KPI results for {{ $period }}.</p>
        <form method="POST" action="{{ route('erp.kpi.recalculate') }}" style="margin-top:16px;display:inline;">
            @csrf
            <input type="hidden" name="period" value="{{ $period }}">
            <button type="submit" class="ks-btn ks-btn-primary">Calculate KPIs</button>
        </form>
    </div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cs = getComputedStyle(document.body);
    const roleAccent = cs.getPropertyValue('--role-grad-2').trim() || '#2563EB';
    const roleAccent2 = cs.getPropertyValue('--role-grad-4').trim() || '#60A5FA';
    const textColor = '#6f7180';
    const gridColor = 'rgba(20,10,50,0.06)';

    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = textColor;

    @php
        $chartUsers = $chartResults->filter(fn($r) => $r->isNotEmpty())->keys()->take(10);
        $chartLabels = $chartUsers->map(fn($uid) => $users->find($uid)?->name ?? 'User '.$uid)->values();
        $chartScores = $chartUsers->map(fn($uid) => round($chartResults[$uid]->avg('score') ?? 0, 1))->values();
        $chartCompletion = $chartUsers->map(fn($uid) => $chartResults[$uid]->count() > 0 ? round($chartResults[$uid]->where('score', '>=', 100)->count() / $chartResults[$uid]->count() * 100, 1) : 0)->values();
        $chartTasks = $chartUsers->map(fn($uid) => $chartResults[$uid]->where('kpiDefinition.slug', 'tasks_assigned')->first()?->actual_value ?? 0)->values();
        $chartDone = $chartUsers->map(fn($uid) => $chartResults[$uid]->where('kpiDefinition.slug', 'tasks_completed')->first()?->actual_value ?? 0)->values();
    @endphp

    const labels = {!! json_encode($chartLabels) !!};
    const scores = {!! json_encode($chartScores) !!};
    const completion = {!! json_encode($chartCompletion) !!};
    const tasks = {!! json_encode($chartTasks) !!};
    const done = {!! json_encode($chartDone) !!};

    new Chart(document.getElementById('completionChart'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Completion %', data: completion, backgroundColor: roleAccent + 'cc', borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100, grid: { color: gridColor }, ticks: { color: textColor } }, x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } } } }
    });
    new Chart(document.getElementById('scoreChart'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Avg Score', data: scores, backgroundColor: roleAccent2 + 'cc', borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } }, x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } } } }
    });
    new Chart(document.getElementById('taskChart'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Assigned', data: tasks, backgroundColor: roleAccent + 'cc', borderRadius: 4 }, { label: 'Completed', data: done, backgroundColor: '#16a34acc', borderRadius: 4 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: textColor, font: { size: 11 } } } }, scales: { y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } }, x: { grid: { display: false }, ticks: { color: textColor, font: { size: 10 } } } } }
    });
});
</script>
@endpush
