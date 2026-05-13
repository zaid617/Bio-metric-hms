@extends('layouts.app')

@section('title', 'Add Inventory Item')

@section('content')
    <x-page-title title="Inventory" subtitle="Add Item" />

    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('inventory.store') }}" method="POST" class="row g-3">
                        @csrf

                        <div class="col-12">
                            <label for="name" class="form-label">Item Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="sku" class="form-label">SKU (Optional)</label>
                            <input type="text" id="sku" name="sku" class="form-control" value="{{ old('sku') }}">
                        </div>

                        <div class="col-md-6">
                            <label for="unit" class="form-label">Unit</label>
                            <input type="text" id="unit" name="unit" class="form-control" value="{{ old('unit', 'pcs') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            @if(user_can_manage_all_branches(auth()->user()))
                                <select id="branch_id" name="branch_id" class="form-select">
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="branch_id" value="{{ auth()->user()->branch_id }}">
                                <input type="text" class="form-control" value="{{ auth()->user()?->branch?->name ?? 'N/A' }}" readonly>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label for="department_id" class="form-label">Department</label>
                            <select id="department_id" name="department_id" class="form-select">
                                <option value="">Select Department</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ (string) old('department_id') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="quantity" class="form-label">Current Quantity</label>
                            <input type="number" step="0.01" min="0" id="quantity" name="quantity" class="form-control" value="{{ old('quantity', 0) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="min_quantity" class="form-label">Minimum Quantity</label>
                            <input type="number" step="0.01" min="0" id="min_quantity" name="min_quantity" class="form-control" value="{{ old('min_quantity', 0) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Item</button>
                            <a href="{{ route('inventory.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
