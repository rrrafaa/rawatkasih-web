<?php

namespace App\Http\Controllers\FamilyVisits;

use App\Contracts\FamilyNewsProvider;
use App\Http\Controllers\Controller;
use App\Models\FamilyVisits\FamilyVisitData;
use App\Services\FamilyVisits\PortalContext;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FamilyPortalController extends Controller
{
    public function index(Request $request, PortalContext $context, FamilyVisitData $data, FamilyNewsProvider $news): View
    {
        $vars = $context->for($request);
        $resident = $vars['resident'];
        $stories = $resident ? $news->publishedFor($resident->id, $data->photoConsent($resident->id)) : [];
        $visits = $resident ? $data->visits($resident->id, null, 1) : null;

        return view('modules.family-visits.home', [...$vars, 'stories' => $stories, 'visits' => $visits, 'activeNav' => 'home']);
    }

    public function news(Request $request, PortalContext $context, FamilyVisitData $data, FamilyNewsProvider $news): View
    {
        $filters = $request->validate(['category' => ['nullable', 'in:semua,aktivitas,kebersamaan'], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $vars = $context->for($request);
        $resident = $vars['resident'];
        $stories = $resident ? $news->publishedFor($resident->id, $data->photoConsent($resident->id)) : [];
        $stories = array_values(array_filter($stories, fn ($s) => (! isset($filters['category']) || $filters['category'] === 'semua' || $s['category'] === $filters['category'])
            && (! isset($filters['date']) || substr($s['published_at'], 0, 10) === $filters['date'])));

        return view('modules.family-visits.news', [...$vars, 'stories' => $stories, 'activeNav' => 'news']);
    }

    public function profile(Request $request, PortalContext $context, FamilyVisitData $data): View
    {
        return view('modules.family-visits.profile', [...$context->for($request), 'account' => $data->account(), 'activeNav' => 'profile']);
    }

    public function media(Request $request, string $asset, PortalContext $context, FamilyVisitData $data): BinaryFileResponse
    {
        abort_unless(config('family_visits.demo_news') && in_array($asset, ['garden', 'lunch', 'morning'], true), 404);
        $vars = $context->for($request);
        abort_unless($vars['resident'] !== null, 404);
        abort_unless($data->photoConsent($vars['resident']->id), 403);

        // Private demo imagery from Figma; never a public URL for real resident media.
        return response()->file(resource_path('demo/family/'.$asset.'.jpg'), [
            'Content-Type' => 'image/jpeg', 'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
