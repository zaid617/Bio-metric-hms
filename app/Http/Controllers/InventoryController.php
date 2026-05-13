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
        $user = auth()->user();

        $items = InventoryItem::with(['branch', 'department'])
            ->when(!user_can_manage_all_branches($user), function ($query) use ($user) {
                $query->where('branch_id', user_branch_id($user));
            })
            ->latest()
            ->get();

        return view('inventory.index', compact('items'));
    }

    public function create()
    {
        $user = auth()->user();
        $branches = user_can_manage_all_branches($user)
            ? Branch::orderBy('name')->get()
            : Branch::where('id', user_branch_id($user))->orderBy('name')->get();
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

        if (!user_can_manage_all_branches(auth()->user())) {
            $validated['branch_id'] = user_branch_id();
        }

        InventoryItem::create($validated);

        return redirect()->route('inventory.index')->with('success', 'Inventory item added successfully.');
    }

    public function edit($id)
    {
        $item = InventoryItem::findOrFail($id);
        $user = auth()->user();
        $branches = user_can_manage_all_branches($user)
            ? Branch::orderBy('name')->get()
            : Branch::where('id', user_branch_id($user))->orderBy('name')->get();
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

        if (!user_can_manage_all_branches(auth()->user())) {
            $validated['branch_id'] = user_branch_id();
        }

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
