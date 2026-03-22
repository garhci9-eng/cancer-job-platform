<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\UserProfile;
use App\Services\ClaudeService;
use App\Services\JobContextService;
use App\Services\LegalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private ClaudeService      $claude,
        private JobContextService  $jobCtx,
        private LegalContextService $legalCtx,
    ) {}

    /**
     * POST /api/chat
     * Streams SSE tokens back to the Vue client.
     */
    public function send(Request $request): StreamedResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $user    = $request->user();
        $message = $request->input('message');

        // ── Audit log ──────────────────────────────────────────────────────
        Log::channel('audit')->info('chat.send', [
            'user_id' => $user->id,
            'ip'      => $request->ip(),
            'len'     => strlen($message),
        ]);

        // ── 5-layer context assembly ───────────────────────────────────────
        $profile     = UserProfile::where('user_id', $user->id)->first();
        $history     = ChatMessage::where('user_id', $user->id)
                            ->latest()->take(10)->get()->reverse()->values();
        $jobContext  = $this->jobCtx->relevantJobs($profile);
        $legalCtx    = $this->legalCtx->relevantGuides($profile);

        // Save user turn
        ChatMessage::create([
            'user_id' => $user->id,
            'role'    => 'user',
            'content' => $message,
        ]);

        $systemPrompt = $this->buildSystemPrompt($profile, $jobContext, $legalCtx);
        $messages     = $this->buildMessages($history, $message);

        return new StreamedResponse(function () use ($user, $systemPrompt, $messages) {
            $fullReply = '';

            $this->claude->streamChat(
                system:   $systemPrompt,
                messages: $messages,
                onToken:  function (string $token) use (&$fullReply) {
                    $fullReply .= $token;
                    echo "data: " . json_encode(['token' => $token]) . "\n\n";
                    ob_flush(); flush();
                },
                onDone: function () {
                    echo "data: " . json_encode(['done' => true]) . "\n\n";
                    ob_flush(); flush();
                },
            );

            // Save assistant turn after stream completes
            ChatMessage::create([
                'user_id' => auth()->id(),
                'role'    => 'assistant',
                'content' => $fullReply,
            ]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function history(Request $request)
    {
        $msgs = ChatMessage::where('user_id', $request->user()->id)
                    ->orderBy('created_at')
                    ->get(['role', 'content', 'created_at']);

        return response()->json(['messages' => $msgs]);
    }

    public function clear(Request $request)
    {
        ChatMessage::where('user_id', $request->user()->id)->delete();
        return response()->json(['ok' => true]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function buildSystemPrompt(?UserProfile $profile, array $jobs, array $legal): string
    {
        $profileBlock = $profile
            ? "## 사용자 프로필\n" . json_encode($profile->toPublicArray(), JSON_UNESCAPED_UNICODE)
            : "## 사용자 프로필\n아직 프로필이 없습니다.";

        $jobBlock = "## 매칭 가능한 일자리 (상위 5개)\n" .
            collect($jobs)->map(fn($j) => "- [{$j['title']}] {$j['company']} | {$j['type']} | {$j['location']}")->implode("\n");

        $legalBlock = "## 관련 법적 지원제도\n" .
            collect($legal)->map(fn($l) => "- {$l['title']}: {$l['summary']}")->implode("\n");

        return <<<PROMPT
당신은 암 투병 중이거나 완치 후 복직·재취업을 준비하는 분들을 돕는 AI 상담사 "희망이"입니다.

{$profileBlock}

{$jobBlock}

{$legalBlock}

## 대화 원칙
- 먼저 공감하고, 그 다음 실질적인 정보를 제공합니다.
- 위 프로필·일자리·제도 데이터를 근거로 구체적인 조언을 합니다.
- 의학적 진단은 하지 않으며, 필요시 전문의 상담을 권장합니다.
- 답변은 간결하고 온화한 톤으로, 마크다운을 활용해 구조화합니다.
- 한국어로 답변합니다.
PROMPT;
    }

    private function buildMessages($history, string $newMessage): array
    {
        $msgs = $history->map(fn($m) => [
            'role'    => $m->role,
            'content' => $m->content,
        ])->toArray();

        $msgs[] = ['role' => 'user', 'content' => $newMessage];
        return $msgs;
    }
}
