<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers with optional search.
     */
    public function index()
    {
        $search = request('search');

        $suppliers = Supplier::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        Supplier::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('suppliers.index')
<<<<<<< HEAD
            ->with('success', __('Supplier added successfully.'));
=======
            ->with('success', 'Supplier berhasil ditambahkan.');
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('suppliers.index')
<<<<<<< HEAD
            ->with('success', __('Supplier updated successfully.'));
=======
            ->with('success', 'Supplier berhasil diperbarui.');
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
    }

    /**
     * Remove the specified supplier from storage.
     * Prevents deletion when supplier is linked to a product.
     */
    public function destroy(Supplier $supplier)
    {
        if ($supplier->products()->exists()) {
<<<<<<< HEAD
            return back()->with('error', __('This supplier cannot be deleted because it is assigned to products.'));
=======
            return back()->with('error', 'Supplier tidak dapat dihapus karena masih digunakan oleh produk.');
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
        }

        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
<<<<<<< HEAD
            ->with('success', __('Supplier deleted successfully.'));
=======
            ->with('success', 'Supplier berhasil dihapus.');
>>>>>>> cfae57bbdb3e98a69b3be2aeae9d4acf3c54b264
    }
}
