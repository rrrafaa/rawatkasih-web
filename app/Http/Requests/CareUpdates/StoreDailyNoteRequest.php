<?php

namespace App\Http\Requests\CareUpdates;

use App\Enums\Role;
use App\Models\CaregiverAssignment;
use App\Models\DailyNote;
use App\Models\SupabaseUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreDailyNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof SupabaseUser || $user->role() !== Role::Caregiver) {
            return false;
        }

        $residentId = $this->input('resident_id');

        if (! is_string($residentId) || $residentId === '') {
            return false;
        }

        return Gate::allows('create', [DailyNote::class, $residentId]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'resident_id' => ['required', 'uuid'],
            'mood' => ['nullable', 'string', 'max:50'],
            'meal_note' => ['nullable', 'string', 'max:500'],
            'internal_note' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resident_id.required' => 'Pilih lansia yang ditugaskan.',
            'resident_id.uuid' => 'Identitas lansia tidak valid.',
            'internal_note.required' => 'Catatan internal wajib diisi.',
            'internal_note.min' => 'Catatan internal minimal 3 karakter.',
            'internal_note.max' => 'Catatan internal maksimal 2000 karakter.',
            'mood.max' => 'Deskripsi mood maksimal 50 karakter.',
            'meal_note.max' => 'Catatan makan maksimal 500 karakter.',
        ];
    }
}
