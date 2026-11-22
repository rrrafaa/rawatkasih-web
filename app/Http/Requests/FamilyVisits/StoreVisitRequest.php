<?php

namespace App\Http\Requests\FamilyVisits;

use App\Services\FamilyVisits\VisitHours;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Validator;

class StoreVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string|In>> */
    public function rules(): array
    {
        $slots = VisitHours::slots();

        return ['resident_id' => ['required', 'uuid'], 'submission_key' => ['required', 'uuid'],
            'visit_date' => ['required', 'date_format:Y-m-d'], 'start_time' => ['bail', 'required', 'date_format:H:i', Rule::in(array_slice($slots, 0, -1))],
            'end_time' => ['bail', 'required', 'date_format:H:i', Rule::in(array_slice($slots, 1))], 'visitor_count' => ['required', 'integer', 'min:1', 'max:20'],
            'primary_visitor_name' => ['required', 'string', 'min:2', 'max:120'], 'note' => ['nullable', 'string', 'max:1000']];
    }

    protected function prepareForValidation(): void
    {
        foreach (['primary_visitor_name', 'note'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['visit_date', 'start_time', 'end_time'])) {
                return;
            }
            $start = CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->input('visit_date').' '.$this->input('start_time'), 'Asia/Jakarta');
            $end = CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->input('visit_date').' '.$this->input('end_time'), 'Asia/Jakarta');
            if ($start->lessThanOrEqualTo(CarbonImmutable::now('Asia/Jakarta'))) {
                $validator->errors()->add('visit_date', 'Tanggal dan waktu kunjungan harus setelah waktu sekarang (WIB).');
            }
            if ($end->lessThanOrEqualTo($start)) {
                $validator->errors()->add('end_time', 'Waktu selesai harus setelah waktu mulai pada hari yang sama.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['required' => ':attribute wajib diisi.', 'uuid' => ':attribute tidak valid.', 'integer' => ':attribute harus berupa bilangan bulat.',
            'start_time.in' => 'Pilih waktu mulai antara 08.00 dan 18.45 WIB dengan interval 15 menit.',
            'end_time.in' => 'Pilih waktu selesai antara 08.15 dan 19.00 WIB dengan interval 15 menit.',
            'visitor_count.min' => 'Jumlah pengunjung minimal 1 orang.', 'visitor_count.max' => 'Jumlah pengunjung maksimal 20 orang.',
            'primary_visitor_name.min' => 'Nama pengunjung minimal 2 karakter.', 'date_format' => 'Format :attribute tidak valid.', 'max' => ':attribute terlalu panjang.'];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'visit_date' => 'tanggal kunjungan',
            'start_time' => 'waktu mulai',
            'end_time' => 'waktu selesai',
            'visitor_count' => 'jumlah pengunjung',
            'primary_visitor_name' => 'nama pengunjung utama',
            'note' => 'catatan tambahan',
        ];
    }
}
