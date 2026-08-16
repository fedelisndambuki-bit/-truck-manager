<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Context Budget
    |--------------------------------------------------------------------------
    |
    | Bounds on how much data the AI data-access layer ever hands to a
    | provider (Groq/OpenRouter). These exist so a large company's data never
    | gets blindly dumped into a prompt — every provider's list-returning
    | methods must respect max_records_per_provider, and the tool-call loop
    | in AiProviderService must respect max_tool_rounds.
    |
    */

    'max_records_per_provider' => env('AI_MAX_RECORDS_PER_PROVIDER', 40),

    'max_context_chars' => env('AI_MAX_CONTEXT_CHARS', 6000),

    'max_tool_rounds' => env('AI_MAX_TOOL_ROUNDS', 3),

    'max_output_tokens' => env('AI_MAX_OUTPUT_TOKENS', 400),

    'max_output_tokens_long' => env('AI_MAX_OUTPUT_TOKENS_LONG', 1000),

    // Used for analytical/comparative/diagnostic/executive-depth chat
    // questions (see AiQuestionClassifier) — a structured Executive Summary/
    // Comparison/Trend/Contributors/Findings/Recommendations response needs
    // real room, but this is still a bounded budget, not "enormous".
    'max_output_tokens_analytical' => env('AI_MAX_OUTPUT_TOKENS_ANALYTICAL', 1400),

    // How far a data point must deviate from its series average (as a
    // percentage) before AiTrendAnalyzer flags it as a notable spike/drop.
    'anomaly_threshold_percent' => env('AI_ANOMALY_THRESHOLD_PERCENT', 30),

];
