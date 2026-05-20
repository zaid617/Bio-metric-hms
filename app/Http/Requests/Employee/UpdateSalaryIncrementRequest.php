<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalaryIncrementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware handles permission
    }

    public function rules(): array
    {
        // Only the reason is editable; salary values are immutable after creation.
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
