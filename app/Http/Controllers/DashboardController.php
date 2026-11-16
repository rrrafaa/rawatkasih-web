<?php

namespace App\Http\Controllers;

use App\Models\SupabaseUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        return redirect()->route($user->role()->value.'.dashboard');
    }

    public function show(Request $request): View
    {
        /** @var SupabaseUser $user */
        $user = $request->user();

        return view($user->role()->value.'.dashboard', ['user' => $user]);
    }
}
