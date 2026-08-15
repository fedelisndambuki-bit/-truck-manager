<?php

namespace App\Services\PostingResolvers;

interface PostingRuleResolver
{
    /**
     * Resolve the balanced set of journal lines for a posting-engine event.
     * Each line: ['account_code' => int, 'debit' => float, 'credit' => float, 'wallet_type' => ?string].
     */
    public function resolveLines(array $context): array;
}
