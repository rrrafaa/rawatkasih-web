<?php

namespace App\Http\Requests\FamilyVisits;

use Illuminate\Foundation\Http\FormRequest;

class ReviewVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['decision' => ['required', 'in:approved,rejected'], 'review_note' => ['nullable', 'required_if:decision,rejected', 'string', 'max:1000']];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('review_note'))) {
            $this->merge(['review_note' => trim($this->input('review_note'))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['review_note.required_if' => 'Alasan wajib diisi ketika menolak kunjungan.', 'decision.in' => 'Keputusan kunjungan tidak valid.', 'review_note.max' => 'Catatan maksimal 1000 karakter.'];
    }
}
