<?php

namespace Tests\Unit\LMS;

use App\Modules\LMS\Gateways\AnthropicAiChecker;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AnthropicAiCheckerTest extends TestCase
{
    public function test_parses_a_successful_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => json_encode([
                        'ai_score' => 88,
                        'likely_ai_generated' => true,
                        'originality_note' => 'Highly uniform sentence structure.',
                    ])],
                ],
            ], 200),
        ]);

        $result = (new AnthropicAiChecker)->check('fake-key', 'Some submission content.');

        $this->assertTrue($result->success);
        $this->assertSame(88, $result->aiScore);
        $this->assertTrue($result->likelyAiGenerated);
        $this->assertSame('Highly uniform sentence structure.', $result->originalityNote);
    }

    public function test_throws_on_a_non_successful_response_so_the_queue_can_retry(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response('rate limited', 429)]);

        $this->expectException(RuntimeException::class);

        (new AnthropicAiChecker)->check('fake-key', 'content');
    }

    public function test_throws_on_an_unparseable_response(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'not json']]], 200),
        ]);

        $this->expectException(RuntimeException::class);

        (new AnthropicAiChecker)->check('fake-key', 'content');
    }
}
