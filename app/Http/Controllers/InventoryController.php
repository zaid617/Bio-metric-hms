<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index()
    {
        $items = InventoryItem::with(['branch', 'department'])
            ->latest()
            ->get();

        return view('inventory.index', compact('items'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('inventory.create', compact('branches', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:inventory_items,sku'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'min_quantity' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        InventoryItem::create($validated);

        return redirect()->route('inventory.index')->with('success', 'Inventory item added successfully.');
    }

    public function edit($id)
    {
        $item = InventoryItem::findOrFail($id);
        $branches = Branch::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('inventory.edit', compact('item', 'branches', 'departments'));
    }

    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('inventory_items', 'sku')->ignore($item->id)],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'max:30'],
            'min_quantity' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        $item->update($validated);

        return redirect()->route('inventory.index')->with('success', 'Inventory item updated successfully.');
    }

    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();

        return redirect()->route('inventory.index')->with('success', 'Inventory item deleted successfully.');
    }
}
