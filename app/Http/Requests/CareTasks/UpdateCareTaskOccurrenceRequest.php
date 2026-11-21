<?php

namespace App\Http\Requests\CareTasks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCareTaskOccurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role()->value === 'caregiver';
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(['completed', 'postponed', 'missed']),
            ],
            'reason' => [
                'nullable',
                'required_if:status,postponed,missed',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Pilih status tugas.',
            'status.in' => 'Status tugas tidak valid.',
            'reason.required_if' => 'Alasan wajib diisi untuk tugas yang ditunda atau terlewat.',
            'reason.max' => 'Alasan maksimal 2.000 karakter.',
        ];
    }
}