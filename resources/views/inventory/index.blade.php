@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
    <x-page-title title="Inventory" subtitle="Management" />

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Inventory Items</h5>
                        @can('inventory.create')
                            <a href="{{ route('inventory.create') }}" class="btn btn-primary">Add Inventory Item</a>
                        @endcan
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Item</th>
                                    <th>SKU</th>
                                    <th>Branch</th>
                                    <th>Department</th>
                                    <th>Quantity</th>
                                    <th>Min Qty</th>
                                    <th>Status</th>
                                    <th style="width: 180px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->sku ?? '-' }}</td>
                                        <td>{{ $item->branch->name ?? '-' }}</td>
                                        <td>{{ $item->department->name ?? '-' }}</td>
                                        <td>{{ number_format((float) $item->quantity, 2) }} {{ $item->unit }}</td>
                                        <td>{{ number_format((float) $item->min_quantity, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $item->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($item->status) }}</span>
                                        </td>
                                        <td class="text-center">
                                            @can('inventory.edit')
                                                <a href="{{ route('inventory.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                            @endcan

                                            @can('inventory.delete')
                                                <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this inventory item?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No inventory items found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
