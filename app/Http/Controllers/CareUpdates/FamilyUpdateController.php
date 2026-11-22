<?php

namespace App\Http\Controllers\CareUpdates;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\CareUpdates\StoreFamilyUpdateRequest;
use App\Http\Requests\CareUpdates\UpdateFamilyUpdateRequest;
use App\Models\CaregiverAssignment;
use App\Models\Consent;
use App\Models\FamilyLink;
use App\Models\FamilyUpdate;
use App\Models\Profile;
use App\Models\Resident;
use App\Models\SupabaseUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FamilyUpdateController extends Controller
{
    public function index(
        Request $request,
        CaregiverAssignment $assignments,
        Resident $residents,
        FamilyUpdate $familyUpdates,
        Consent $consents,
        Profile $profiles
    ): View {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $date = now('Asia/Jakarta')->toDateString();

        // Ambil penugasan aktif caregiver
        $assignmentRows = $assignments->activeForCurrentCaregiver($date);
        $assignedResidentIds = array_values(array_unique(
            array_column($assignmentRows, 'resident_id')
        ));

        $residentRows = $residents->forAssignedResidents($assignedResidentIds);
        $residentsById = collect($residentRows)->keyBy('id');

        // Cek consent foto & visibilitas untuk masing-masing lansia yang ditugaskan
        $residentsWithConsent = [];
        foreach ($residentRows as $res) {
            $res['photo_consent'] = $consents->hasPhotoConsent($res['id']);
            $res['visibility_consent'] = $consents->hasVisibilityConsent($res['id']);
            $residentsWithConsent[] = $res;
        }

        // Filter per lansia & status
        $selectedResidentId = $request->query('resident');
        if (! is_string($selectedResidentId) || ! $residentsById->has($selectedResidentId)) {
            $selectedResidentId = null;
        }

        $statusFilter = $request->query('status');
        if (! in_array($statusFilter, ['draft', 'published'], true)) {
            $statusFilter = null;
        }

        $queryResidentIds = $selectedResidentId !== null
            ? [$selectedResidentId]
            : array_column($residentRows, 'id');

        $page = max(1, min(1000, $request->integer('page', 1)));
        $perPage = 20;

        $updateRows = [];
        if ($queryResidentIds !== []) {
            $updateRows = $familyUpdates->forResidents($queryResidentIds, $statusFilter, $page, $perPage);
        }

        // Ambil nama author
        $authorIds = array_values(array_unique(array_filter(
            array_column($updateRows, 'author_id'),
            fn ($id) => is_string($id) && $id !== ''
        )));

        $authorNames = [];
        if ($authorIds !== []) {
            $token = $request->session()->get('supabase.access_token');
            if (is_string($token) && $token !== '') {
                $authorNames = $profiles->namesByIds($authorIds, $token);
            }
        }

        $updatesList = [];
        foreach ($updateRows as $row) {
            $rId = $row['resident_id'] ?? null;
            $aId = $row['author_id'] ?? null;
            $photoPath = $row['photo_path'] ?? null;

            // URL foto privat via route terotorisasi
            $photoUrl = null;
            if (filled($photoPath)) {
                $photoUrl = route('care-updates.family-updates.photo', ['updateId' => $row['id']]);
            }

            $updatesList[] = array_merge($row, [
                'resident_name' => is_string($rId) && $residentsById->has($rId)
                    ? $residentsById->get($rId)['full_name']
                    : 'Lansia',
                'resident_room' => is_string($rId) && $residentsById->has($rId)
                    ? ($residentsById->get($rId)['room'] ?? '-')
                    : '-',
                'author_name' => is_string($aId) && isset($authorNames[$aId])
                    ? $authorNames[$aId]
                    : ($aId === $user->id() ? $user->name() : 'Caregiver'),
                'photo_url' => $photoUrl,
            ]);
        }

        return view('modules.care-updates.family-updates.index', [
            'user' => $user,
            'date' => $date,
            'residents' => $residentsWithConsent,
            'selectedResidentId' => $selectedResidentId,
            'statusFilter' => $statusFilter,
            'updates' => $updatesList,
            'page' => $page,
            'hasMore' => count($updateRows) === $perPage,
        ]);
    }

    public function store(
        StoreFamilyUpdateRequest $request,
        FamilyUpdate $familyUpdates,
        Consent $consents
    ): RedirectResponse {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $data = $request->validated();
        $residentId = $data['resident_id'];
        $action = $data['action']; // 'draft' or 'publish'
        $hasPhoto = $request->hasFile('photo');

        $status = $action === 'publish' ? 'published' : 'draft';

        // Jika akan dipublikasikan, validasi consent visibilitas & foto aktif
        if ($action === 'publish') {
            if (! $consents->hasVisibilityConsent($residentId)) {
                throw ValidationException::withMessages([
                    'resident_id' => 'Lansia ini belum memiliki izin visibilitas keluarga (consent family_visibility). Kabar hanya dapat disimpan sebagai draft.',
                ]);
            }

            if ($hasPhoto && ! $consents->hasPhotoConsent($residentId)) {
                throw ValidationException::withMessages([
                    'photo' => 'Lansia ini belum memiliki izin berbagi foto (consent photo_share aktif). Unggah foto tidak diperbolehkan.',
                ]);
            }
        }
        $photoPath = null;
        if ($hasPhoto) {
            $file = $request->file('photo');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = Str::uuid().'.'.$extension;

            // Simpan ke private disk (storage/app/private/family_updates)
            $photoPath = $file->storeAs('family_updates', $filename, 'local');
        }

        $publishedAt = $status === 'published' ? now('Asia/Jakarta')->toIso8601String() : null;

        $familyUpdates->createForCurrentCaregiver([
            'resident_id' => $residentId,
            'author_id' => $user->id(),
            'summary' => trim($data['summary']),
            'photo_path' => $photoPath,
            'status' => $status,
            'published_at' => $publishedAt,
        ]);

        $message = $status === 'published'
            ? 'Kabar keluarga berhasil dipublikasikan.'
            : 'Draft kabar keluarga berhasil disimpan.';

        return redirect()
            ->route('care-updates.family-updates.index', ['resident' => $residentId])
            ->with('status', $message);
    }

    public function edit(
        string $updateId,
        Request $request,
        FamilyUpdate $familyUpdates,
        Resident $residents,
        Consent $consents
    ): View {
        $update = $familyUpdates->findForCurrentUser($updateId);
        abort_unless(is_array($update), 404);

        Gate::authorize('update', [FamilyUpdate::class, $update]);

        $residentId = $update['resident_id'];
        $resident = $residents->findForCurrentUser($residentId);
        $residentName = $resident['full_name'] ?? 'Lansia';
        $residentRoom = $resident['room'] ?? '-';

        $hasPhotoConsent = $consents->hasPhotoConsent($residentId);
        $hasVisibilityConsent = $consents->hasVisibilityConsent($residentId);

        $photoUrl = null;
        if (filled($update['photo_path'] ?? null)) {
            $photoUrl = route('care-updates.family-updates.photo', ['updateId' => $update['id']]);
        }

        return view('modules.care-updates.family-updates.edit', [
            'user' => $request->user(),
            'update' => $update,
            'residentName' => $residentName,
            'residentRoom' => $residentRoom,
            'hasPhotoConsent' => $hasPhotoConsent,
            'hasVisibilityConsent' => $hasVisibilityConsent,
            'photoUrl' => $photoUrl,
        ]);
    }

    public function update(
        UpdateFamilyUpdateRequest $request,
        string $updateId,
        FamilyUpdate $familyUpdates,
        Consent $consents
    ): RedirectResponse {
        $update = $familyUpdates->findForCurrentUser($updateId);
        abort_unless(is_array($update), 404);

        Gate::authorize('update', [FamilyUpdate::class, $update]);

        $residentId = $update['resident_id'];
        $data = $request->validated();
        $action = $data['action'];
        $hasPhoto = $request->hasFile('photo');
        $removePhoto = $request->boolean('remove_photo');

        $currentPhoto = $update['photo_path'] ?? null;
        $finalPhotoPath = $currentPhoto;

        if ($removePhoto && ! $hasPhoto) {
            if (filled($currentPhoto) && Storage::disk('local')->exists($currentPhoto)) {
                Storage::disk('local')->delete($currentPhoto);
            }
            $finalPhotoPath = null;
        }

        if ($hasPhoto) {
            if (filled($currentPhoto) && Storage::disk('local')->exists($currentPhoto)) {
                Storage::disk('local')->delete($currentPhoto);
            }
            $file = $request->file('photo');
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = Str::uuid().'.'.$extension;
            $finalPhotoPath = $file->storeAs('family_updates', $filename, 'local');
        }

        $status = $action === 'publish' ? 'published' : 'draft';

        if ($action === 'publish') {
            if (! $consents->hasVisibilityConsent($residentId)) {
                throw ValidationException::withMessages([
                    'summary' => 'Lansia ini belum memiliki izin visibilitas keluarga (consent family_visibility). Kabar hanya dapat disimpan sebagai draft.',
                ]);
            }

            if (filled($finalPhotoPath) && ! $consents->hasPhotoConsent($residentId)) {
                throw ValidationException::withMessages([
                    'photo' => 'Lansia ini belum memiliki izin berbagi foto (consent photo_share aktif). Unggah foto tidak diperbolehkan.',
                ]);
            }
        }

        $payload = [
            'summary' => trim($data['summary']),
            'photo_path' => $finalPhotoPath,
            'status' => $status,
            'updated_at' => now('Asia/Jakarta')->toIso8601String(),
        ];

        if ($status === 'published' && empty($update['published_at'])) {
            $payload['published_at'] = now('Asia/Jakarta')->toIso8601String();
        }

        $familyUpdates->updateForCurrentCaregiver($updateId, $payload);

        $message = $status === 'published'
            ? 'Kabar keluarga berhasil diterbitkan.'
            : 'Perubahan draft kabar keluarga berhasil disimpan.';

        return redirect()
            ->route('care-updates.family-updates.index', ['resident' => $residentId])
            ->with('status', $message);
    }

    public function destroy(
        string $updateId,
        Request $request,
        FamilyUpdate $familyUpdates
    ): RedirectResponse {
        $update = $familyUpdates->findForCurrentUser($updateId);
        abort_unless(is_array($update), 404);

        Gate::authorize('delete', [FamilyUpdate::class, $update]);

        if (filled($update['photo_path'] ?? null) && Storage::disk('local')->exists($update['photo_path'])) {
            Storage::disk('local')->delete($update['photo_path']);
        }

        $familyUpdates->deleteForCurrentCaregiver($updateId);

        return redirect()
            ->route('care-updates.family-updates.index', ['resident' => $update['resident_id'] ?? null])
            ->with('status', 'Kabar keluarga berhasil dihapus.');
    }

    /**
     * Route privat untuk menampilkan foto dengan otorisasi ketat.
     */
    public function showPhoto(
        Request $request,
        string $updateId,
        FamilyUpdate $familyUpdates,
        CaregiverAssignment $assignments,
        FamilyLink $familyLinks,
        Consent $consents
    ): BinaryFileResponse {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $update = $familyUpdates->findForCurrentUser($updateId);
        abort_unless(is_array($update), 404);

        $photoPath = $update['photo_path'] ?? null;
        abort_unless(is_string($photoPath) && $photoPath !== '', 404);

        $residentId = $update['resident_id'] ?? null;
        abort_unless(is_string($residentId) && $residentId !== '', 404);

        // 1. Otorisasi Per Role
        if ($user->role() === Role::Caregiver) {
            // Caregiver harus aktif ditugaskan ke lansia ini
            abort_unless($assignments->isAssignedToResident($residentId), 403);
        } elseif ($user->role() === Role::Family) {
            // Keluarga HANYA boleh jika kabar berstatus published
            abort_unless(($update['status'] ?? '') === 'published', 403);

            // Dan relasi family_links berstatus verified
            abort_unless($familyLinks->isVerifiedForResident($residentId), 403);

            // Dan izin foto masih berstatus granted saat file diakses
            abort_unless($consents->hasPhotoConsent($residentId), 403);
        }

        // 2. Cek keberadaan file di private disk
        abort_unless(Storage::disk('local')->exists($photoPath), 404);

        $fullPath = Storage::disk('local')->path($photoPath);
        $mime = mime_content_type($fullPath) ?: 'image/jpeg';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
