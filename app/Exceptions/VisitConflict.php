<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class VisitConflict extends RuntimeException
{
    public function render(Request $request): Response
    {
        $message = 'Pengajuan sudah berubah atau sudah diputuskan. Muat ulang untuk melihat status terbaru.';

        return $request->expectsJson()
            ? response()->json(['message' => $message], 409)
            : response()->view('errors.visit-conflict', compact('message'), 409, ['Cache-Control' => 'no-store, private']);
    }
}
