<?php

namespace App\Http\Requests\CareUpdates;

use App\Enums\Role;
use App\Models\FamilyUpdate;
use App\Models\SupabaseUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreFamilyUpdateRequest extends FormRequest
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

        return Gate::allows('create', [FamilyUpdate::class, $residentId]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'resident_id' => ['required', 'uuid'],
            'summary' => ['required', 'string', 'min:5', 'max:2000'],
            'action' => ['required', 'in:draft,publish'],
            'photo' => [
                'nullable',
                'file',
                'mimes:jpeg,jpg,png,webp',
                'max:3072', // 3 MB = 3072 KB
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resident_id.required' => 'Pilih lansia terlebih dahulu.',
            'resident_id.uuid' => 'ID lansia tidak valid.',
            'summary.required' => 'Ringkasan kabar wajib diisi.',
            'summary.min' => 'Ringkasan kabar minimal 5 karakter.',
            'summary.max' => 'Ringkasan kabar maksimal 2000 karakter.',
            'action.required' => 'Pilih tindakan simpan sebagai draft atau terbitkan.',
            'action.in' => 'Tindakan publikasi tidak valid.',
            'photo.file' => 'Berkas foto tidak valid.',
            'photo.mimes' => 'Format foto harus berupa JPEG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 3 MB.',
        ];
    }
}
