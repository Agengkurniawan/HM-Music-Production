<?php

namespace App\Http\Controllers;

use App\Support\HeaderNotificationReadState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationReadController extends Controller
{
    public function store(Request $request, HeaderNotificationReadState $readState): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:180'],
        ]);

        $readState->markRead($validated['key'], $request->user());

        return response()->json([
            'read' => true,
        ]);
    }
}
