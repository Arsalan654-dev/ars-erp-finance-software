<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\EmployeeKpiResult;
use App\Models\KpiDefinition;
use App\Services\KpiService;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    public function index(Request $request)
    {
        $definitions = KpiDefinition::with(['targets'])->where('is_active', true)->get();

        $period = $request->get('period', now()->format('Y-m'));
        $results = EmployeeKpiResult::with(['kpiDefinition', 'user'])
            ->where('period', $period)
            ->paginate(20);

        $chartResults = EmployeeKpiResult::with(['kpiDefinition', 'user'])
            ->where('period', $period)
            ->get()
            ->groupBy('user_id');

        $users = \App\Models\User::where('is_active', true)->orderBy('name')->get();

        return view('erp.kpi.index', compact('definitions', 'results', 'chartResults', 'period', 'users'));
    }

    public function definitions()
    {
        $definitions = KpiDefinition::latest()->paginate(15);

        return view('erp.kpi.definitions', compact('definitions'));
    }

    public function storeDefinition(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'unique:kpi_definitions,slug'],
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'unit' => ['nullable', 'string'],
            'calculation_method' => ['nullable', 'in:auto,manual'],
            'frequency' => ['nullable', 'in:daily,weekly,monthly,quarterly'],
            'direction' => ['nullable', 'in:higher_better,lower_better'],
        ]);

        KpiDefinition::create($data);

        return back()->with('success', 'KPI definition created.');
    }

    public function recalculate(Request $request)
    {
        $period = $request->get('period', now()->format('Y-m'));
        $count = KpiService::recalculateAll($period);

        return back()->with('success', "KPI recalculated for {$count} metrics.");
    }
}