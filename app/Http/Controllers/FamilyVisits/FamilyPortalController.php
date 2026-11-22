<?php

namespace App\Http\Controllers\FamilyVisits;

use App\Contracts\FamilyNewsProvider;
use App\Http\Controllers\Controller;
use App\Models\FamilyVisits\FamilyVisitData;
use App\Models\FamilyVisits\Resident;
use App\Models\FamilyVisits\VisitRequest;
use App\Services\FamilyVisits\FamilyMediaStorage;
use App\Services\FamilyVisits\PortalContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class FamilyPortalController extends Controller
{
    public function index(Request $request, PortalContext $context, FamilyVisitData $data, FamilyNewsProvider $news): View
    {
        $vars = $context->for($request);
        $resident = $vars['resident'];
        $photoConsent = $resident !== null && $data->photoConsent($resident->id);
        $stories = $resident ? $news->publishedFor($resident->id, $photoConsent) : [];
        $visits = $resident ? $data->visits($resident->id, null, 1) : null;

        return view('modules.family-visits.home', [...$vars, 'stories' => $stories, 'visits' => $visits,
            'demoPortrait' => $photoConsent && $this->hasDemoPortrait($resident),
            'realPortrait' => $photoConsent && $resident && filled($resident->profile_photo_path), 'activeNav' => 'home']);
    }

    public function news(Request $request, PortalContext $context, FamilyVisitData $data, FamilyNewsProvider $news): View
    {
        $filters = $request->validate(['category' => ['nullable', 'in:semua,aktivitas,kebersamaan'], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $vars = $context->for($request);
        $resident = $vars['resident'];
        $photoConsent = $resident !== null && $data->photoConsent($resident->id);
        $stories = $resident ? $news->publishedFor($resident->id, $photoConsent) : [];
        $dates = [];
        $visibleStories = [];
        foreach ($stories as $story) {
            $publishedAt = CarbonImmutable::parse($story['published_at'])->setTimezone('Asia/Jakarta')->settings(['locale' => 'id']);
            $date = $publishedAt->toDateString();
            $dates[$date] = $publishedAt->translatedFormat('d F Y');
            if ((! isset($filters['date']) || $filters['date'] === $date)
                && (! isset($filters['category']) || $filters['category'] === 'semua' || $filters['category'] === $story['category'])) {
                $visibleStories[] = [...$story, 'publishedAt' => $publishedAt];
            }
        }

        return view('modules.family-visits.news', [...$vars, 'stories' => $visibleStories, 'dates' => $dates,
            'demoPortrait' => $photoConsent && $this->hasDemoPortrait($resident),
            'realPortrait' => $photoConsent && $resident && filled($resident->profile_photo_path), 'activeNav' => 'news']);
    }

    public function profile(Request $request, PortalContext $context, FamilyVisitData $data): View
    {
        return view('modules.family-visits.profile', [...$context->for($request), 'account' => $data->account(), 'activeNav' => 'profile']);
    }

    public function updateProfile(Request $request, PortalContext $context, FamilyVisitData $data): RedirectResponse
    {
        $fields = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9][0-9(). -]*$/D'],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'full_name.max' => 'Nama lengkap maksimal 120 karakter.',
            'phone.max' => 'Nomor telepon maksimal 32 karakter.',
            'phone.regex' => 'Masukkan nomor telepon yang valid.',
        ]);
        $resident = $context->for($request)['resident'];
        $data->updateAccount($fields['full_name'], $fields['phone'] ?? null);

        return redirect()->route('family.profile', $resident ? ['resident' => $resident->id] : [])
            ->with('status', 'Profil akun berhasil diperbarui.');
    }

    public function media(Request $request, string $asset, PortalContext $context, FamilyVisitData $data): BinaryFileResponse
    {
        abort_unless(config('family_visits.demo_news') && in_array($asset, ['garden', 'lunch', 'morning', 'sulastri'], true), 404);
        $vars = $context->for($request);
        $resident = $vars['resident'];
        abort_unless($resident !== null && ($asset !== 'sulastri' || $this->hasDemoPortrait($resident)), 404);
        abort_unless($data->photoConsent($resident->id), 403);

        $file = $asset === 'sulastri' ? 'resident/sulastri/profil.jpg' : 'resident/contoh-kabar/'.$asset.'.jpg';

        abort_unless(is_file(resource_path($file)), 404);

        return $this->photoResponse(resource_path($file));
    }

    public function avatar(Request $request, FamilyVisitData $data, FamilyMediaStorage $media): Response
    {
        return $this->familyAvatar((string) $request->user()->getAuthIdentifier(), $data, $media);
    }

    public function requesterAvatar(VisitRequest $visit, FamilyVisitData $data, FamilyMediaStorage $media): Response
    {
        return $this->familyAvatar($visit->requester_id, $data, $media);
    }

    private function familyAvatar(string $id, FamilyVisitData $data, FamilyMediaStorage $media): Response
    {
        abort_unless(Str::isUuid($id), 404);
        $path = $data->avatarPath($id);
        if (is_string($path) && str_starts_with($path, 'profiles/'.$id.'/avatar/')) {
            return $media->download($path);
        }
        // Backwards-compatible local illustration for profiles without uploaded photos.
        $photo = resource_path('keluarga/'.$id.'.jpg');

        return $this->photoResponse(is_file($photo) ? $photo : resource_path('keluarga/ilustrasi-profil.jpg'));
    }

    private function photoResponse(string $path): BinaryFileResponse
    {
        return response()->file($path, [
            'Content-Type' => 'image/jpeg', 'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function hasDemoPortrait(Resident $resident): bool
    {
        $demoResidentId = config('family_visits.demo_portrait_resident_id');

        return config('family_visits.demo_news')
            && is_string($demoResidentId)
            && $demoResidentId !== ''
            && $resident->id === $demoResidentId
            && is_file(resource_path('resident/sulastri/profil.jpg'));
    }
}
