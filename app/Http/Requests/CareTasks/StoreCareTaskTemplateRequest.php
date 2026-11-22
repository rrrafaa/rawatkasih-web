<?php

namespace App\Http\Requests\CareTasks;

use Illuminate\Foundation\Http\FormRequest;

class StoreCareTaskTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role()->value === 'admin';
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'resident_id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:Makan,Mandi,Aktivitas,Obat'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'frequency' => ['required', 'in:daily,weekly'],
            'days_of_week' => ['required_if:frequency,weekly', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:0,6', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'resident_id.required' => 'Pilih lansia terlebih dahulu.',
            'title.required' => 'Nama tugas wajib diisi.',
            'category.required' => 'Pilih kategori tugas.',
            'scheduled_time.required' => 'Tentukan jam pelaksanaan.',
            'frequency.required' => 'Pilih frekuensi pengulangan.',
            'days_of_week.required_if' => 'Pilih minimal satu hari.',
            'days_of_week.min' => 'Pilih minimal satu hari.',
        ];
    }
}
