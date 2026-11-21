<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;

class WarehouseController extends Controller
{
    /**
     * Display a listing of warehouses with optional search.
     */
    public function index()
    {
        $search = request('search');

        $warehouses = Warehouse::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('warehouses.index', compact('warehouses', 'search'));
    }

    /**
     * Show the form for creating a new warehouse.
     */
    public function create()
    {
        return view('warehouses.create');
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(StoreWarehouseRequest $request)
    {
        Warehouse::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('warehouses.index')
            ->with('success', __('Warehouse added successfully.'));
    }

    /**
     * Show the form for editing the specified warehouse.
     */
    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse'));
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $warehouse->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('warehouses.index')
            ->with('success', __('Warehouse updated successfully.'));
    }

    /**
     * Remove the specified warehouse from storage.
     * Prevents deletion when warehouse has stock or movement history.
     */
    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->stocks()->where('quantity', '>', 0)->exists()) {
            return back()->with('error', __('This warehouse cannot be deleted while it contains stock.'));
        }

        if ($warehouse->stockMovements()->exists()) {
            return back()->with('error', __('This warehouse cannot be deleted because it has stock movement history.'));
        }

        $warehouse->delete();

        return redirect()
            ->route('warehouses.index')
            ->with('success', __('Warehouse deleted successfully.'));
    }
}
