<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\SalesPackage;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SalesPackageController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesPackage::query();

        if ($request->get('q')) {
            $q = $request->get('q');
            $query->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $packages = $query->withCount('items')->orderBy('name')->paginate(20)->withQueryString();

        return view('erp.sales-packages.index', compact('packages'));
    }

    public function create()
    {
        return view('erp.sales-packages.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $items = $this->validatedItems($request);
        if ($items !== []) {
            // Bundle price is always the sum of its lines.
            $data['unit_price'] = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
        }

        $package = SalesPackage::create($data);
        $this->syncItems($package, $items);

        AuditService::log('SALES_PACKAGE_CREATE', 'sales_package', $package->id, ['name' => $package->name]);

        return redirect()->route('erp.sales-packages.index')->with('success', 'Package created.');
    }

    public function edit(SalesPackage $salesPackage)
    {
        $salesPackage->load('items');

        return view('erp.sales-packages.edit', ['package' => $salesPackage]);
    }

    public function update(Request $request, SalesPackage $salesPackage)
    {
        $data = $this->validated($request);

        $items = $this->validatedItems($request);
        if ($items !== []) {
            $data['unit_price'] = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
        }

        $salesPackage->update($data);
        $this->syncItems($salesPackage, $items);

        AuditService::log('SALES_PACKAGE_UPDATE', 'sales_package', $salesPackage->id, ['name' => $salesPackage->name]);

        return redirect()->route('erp.sales-packages.index')->with('success', 'Package updated.');
    }

    public function toggle(SalesPackage $salesPackage)
    {
        $salesPackage->update(['is_active' => ! $salesPackage->is_active]);

        AuditService::log('SALES_PACKAGE_TOGGLE', 'sales_package', $salesPackage->id, ['is_active' => $salesPackage->is_active]);

        return back()->with('success', 'Package '.($salesPackage->is_active ? 'activated' : 'deactivated').'.');
    }

    public function destroy(SalesPackage $salesPackage)
    {
        $salesPackage->delete();

        AuditService::log('SALES_PACKAGE_DELETE', 'sales_package', $salesPackage->id, ['name' => $salesPackage->name]);

        return redirect()->route('erp.sales-packages.index')->with('success', 'Package deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'in:USD,GBP,EUR'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    /**
     * Optional bundle lines. Empty when the package is a legacy
     * single-price template.
     */
    private function validatedItems(Request $request): array
    {
        $data = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:500'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        return collect($data['items'] ?? [])
            ->filter(fn ($item) => trim((string) ($item['description'] ?? '')) !== '')
            ->values()
            ->map(fn ($item, $index) => [
                'description' => trim($item['description']),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'sort_order' => $index,
            ])
            ->all();
    }

    private function syncItems(SalesPackage $package, array $items): void
    {
        $package->items()->delete();
        foreach ($items as $item) {
            $package->items()->create($item);
        }
    }
}
