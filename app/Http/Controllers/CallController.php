<?php

namespace App\Http\Controllers;

use App\Models\CallSignal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function room(string $room)
    {
        return view('calls.room', ['room' => $room]);
    }

    public function index(Request $request, string $room): JsonResponse
    {
        $afterId = max(0, (int) $request->query('after_id', 0));
        $signals = CallSignal::query()
            ->where('room', $room)
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(200)
            ->get(['id', 'sender', 'type', 'payload', 'created_at']);

        return response()->json([
            'data' => $signals,
        ]);
    }

    public function store(Request $request, string $room): JsonResponse
    {
        $data = $request->validate([
            'sender' => 'nullable|string|max:64',
            'type' => 'required|string|max:32',
            'payload' => 'nullable',
        ]);

        $signal = CallSignal::create([
            'room' => $room,
            'sender' => $data['sender'] ?? null,
            'type' => $data['type'],
            'payload' => isset($data['payload']) ? json_encode($data['payload']) : null,
        ]);

        return response()->json([
            'id' => $signal->id,
        ], 201);
    }
}
