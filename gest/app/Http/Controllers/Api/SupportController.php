<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\User;
use App\Support\CurrentGymResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeGymUser($request);
        $gymId = CurrentGymResolver::id();

        SupportMessage::query()
            ->where('gym_id', $gymId)
            ->where('sender_role', UserRole::SuperAdmin->value)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = SupportMessage::query()
            ->where('gym_id', $gymId)
            ->with('sender:id,name')
            ->oldest()
            ->get()
            ->map(fn (SupportMessage $message): array => $this->format($message));

        return response()->json(['data' => $messages]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->authorizeGymUser($request);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        $message = SupportMessage::query()->create([
            'gym_id' => CurrentGymResolver::id(),
            'user_id' => $user->id,
            'sender_role' => $user->roleEnum()?->value ?? UserRole::Admin->value,
            'category' => 'message',
            'body' => trim($data['body']),
        ]);

        return response()->json([
            'message' => 'Message envoyé au Super Admin.',
            'data' => $this->format($message->load('sender:id,name')),
        ], 201);
    }

    private function authorizeGymUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && in_array($user->roleEnum(), [UserRole::Admin, UserRole::Receptionist], true), 403);

        return $user;
    }

    /** @return array<string, mixed> */
    private function format(SupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'category' => $message->category,
            'body' => $message->body,
            'sender_role' => $message->sender_role,
            'sender_name' => $message->sender?->name,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
