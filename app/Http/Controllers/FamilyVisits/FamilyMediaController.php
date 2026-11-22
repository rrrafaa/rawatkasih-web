<?php

namespace App\Http\Controllers\FamilyVisits;

use App\Exceptions\SupabaseUnavailable;
use App\Http\Controllers\Controller;
use App\Models\FamilyVisits\FamilyVisitData;
use App\Services\FamilyVisits\FamilyMediaStorage;
use App\Services\SupabaseClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FamilyMediaController extends Controller
{
    private function token(Request $request): string
    {
        $token = $request->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);

        return $token;
    }

    public function resident(Request $request, string $resident, FamilyVisitData $data, FamilyMediaStorage $media): Response
    {
        abort_unless(Str::isUuid($resident), 404);
        $linked = $data->resident($resident);
        abort_unless($data->photoConsent($linked->id), 403);
        $path = (string) ($linked->profile_photo_path ?? '');
        abort_unless(str_starts_with($path, $linked->id.'/profile/'), 404);

        return $media->download($path);
    }

    public function update(Request $request, string $update, FamilyVisitData $data, FamilyMediaStorage $media, SupabaseClient $client): Response
    {
        $input = $request->validate(['resident' => ['required', 'uuid']]);
        abort_unless(Str::isUuid($update), 404);
        $linked = $data->resident($input['resident']);
        abort_unless($data->photoConsent($linked->id), 403);
        $response = $client->request('GET', '/rest/v1/family_updates', [
            'select' => 'photo_path',
            'id' => 'eq.'.$update,
            'resident_id' => 'eq.'.$linked->id,
            'status' => 'eq.published',
            'limit' => 1,
        ], $this->token($request));
        abort_if(in_array($response->status(), [401, 403], true), 403);
        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }
        $path = (string) ($response->json('0.photo_path') ?? '');
        abort_unless(str_starts_with($path, $linked->id.'/updates/'), 404);

        return $media->download($path);
    }

    public function uploadAvatar(Request $request, FamilyMediaStorage $media, SupabaseClient $client): RedirectResponse
    {
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072']]);
        $userId = (string) $request->user()->getAuthIdentifier();
        abort_unless(Str::isUuid($userId), 403);
        $path = $media->upload($request->file('photo'), 'profiles/'.$userId.'/avatar');
        $response = $client->request('POST', '/rest/v1/rpc/set_my_avatar_path',
            ['p_path' => $path], $this->token($request));
        abort_if(in_array($response->status(), [401, 403], true), 403);
        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }

        return redirect()->route('family.profile')->with('status', 'Foto profil berhasil diperbarui.');
    }

    public function admin(Request $request, SupabaseClient $client): View
    {
        $token = $this->token($request);
        $residents = $client->request('GET', '/rest/v1/residents', [
            'select' => 'id,full_name', 'order' => 'full_name.asc', 'limit' => 100,
        ], $token);
        $updates = $client->request('GET', '/rest/v1/family_updates', [
            'select' => 'id,resident_id,summary,status', 'order' => 'created_at.desc', 'limit' => 100,
        ], $token);
        if (! $residents->successful() || ! $updates->successful()) {
            throw new SupabaseUnavailable;
        }

        return view('modules.family-visits.admin.media', [
            'user' => $request->user(), 'residents' => $residents->json(), 'updates' => $updates->json(),
        ]);
    }

    public function uploadResident(Request $request, FamilyMediaStorage $media, SupabaseClient $client): RedirectResponse
    {
        $input = $request->validate([
            'resident_id' => ['required', 'uuid'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);
        $id = $input['resident_id'];
        $token = $this->token($request);
        $resident = $client->request('GET', '/rest/v1/residents', ['select' => 'id', 'id' => 'eq.'.$id, 'limit' => 1], $token);
        abort_unless($resident->successful() && is_array($resident->json('0')), 404);
        $path = $media->upload($request->file('photo'), $id.'/profile');
        $result = $client->request('PATCH', '/rest/v1/residents?id=eq.'.$id,
            ['profile_photo_path' => $path], $token, ['Prefer' => 'return=representation']);
        abort_if(in_array($result->status(), [401, 403], true), 403);
        if (! $result->successful()) {
            throw new SupabaseUnavailable;
        }
        abort_if(count($result->json() ?? []) === 0, 404);

        return redirect()->route('admin.family-media')->with('status', 'Foto lansia berhasil diperbarui.');
    }

    public function uploadUpdate(Request $request, FamilyMediaStorage $media, SupabaseClient $client): RedirectResponse
    {
        $input = $request->validate([
            'update_id' => ['required', 'uuid'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);
        $token = $this->token($request);
        $row = $client->request('GET', '/rest/v1/family_updates', [
            'select' => 'id,resident_id', 'id' => 'eq.'.$input['update_id'], 'limit' => 1,
        ], $token);
        abort_unless($row->successful() && is_string($row->json('0.resident_id')), 404);
        $path = $media->upload($request->file('photo'), $row->json('0.resident_id').'/updates');
        $result = $client->request('PATCH', '/rest/v1/family_updates?id=eq.'.$input['update_id'],
            ['photo_path' => $path], $token, ['Prefer' => 'return=representation']);
        abort_if(in_array($result->status(), [401, 403], true), 403);
        if (! $result->successful()) {
            throw new SupabaseUnavailable;
        }
        abort_if(count($result->json() ?? []) === 0, 404);

        return redirect()->route('admin.family-media')->with('status', 'Foto kabar berhasil diperbarui.');
    }
}
