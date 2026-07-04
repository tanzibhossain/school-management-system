<?php

/*
|--------------------------------------------------------------------------
| LMS AI assignment checker settings
|--------------------------------------------------------------------------
| AnthropicAiChecker calls the real Anthropic Messages API using each
| school's own encrypted lms_ai_api_key (schools.lms_ai_api_key) — there is
| no platform-level fallback key, matching the per-school-credential
| convention already established by Sms/Payment.
*/

return [

    'ai_api_base' => env('LMS_AI_API_BASE', 'https://api.anthropic.com/v1/messages'),

    'ai_api_version' => env('LMS_AI_API_VERSION', '2023-06-01'),

    'ai_model' => env('LMS_AI_MODEL', 'claude-3-5-haiku-latest'),

    'ai_timeout_seconds' => env('LMS_AI_TIMEOUT_SECONDS', 30),

    // Content sent to the model is truncated to this many characters —
    // keeps prompt size (and cost) bounded for large submissions.
    'ai_max_content_chars' => env('LMS_AI_MAX_CONTENT_CHARS', 8000),

];
