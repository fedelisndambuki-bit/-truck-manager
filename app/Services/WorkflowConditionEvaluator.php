<?php

namespace App\Services;

/**
 * A deliberately small, safe rule evaluator for workflow Decision/Condition
 * nodes — structured JSON only (field/operator/value + AND/OR/nesting), never
 * eval() or a free-text expression parser. Mirrors the "whitelist over
 * clever" decision made for the BI module's Custom Report Builder.
 *
 * Rule shape: {"field": "amount", "operator": ">", "value": 50000}
 *             {"and": [...]} / {"or": [...]} for nesting.
 * An empty/null rule always evaluates true (an "else"/default branch).
 */
class WorkflowConditionEvaluator
{
    public function evaluate(?array $rule, array $data): bool
    {
        if (empty($rule)) {
            return true;
        }

        if (isset($rule['and']) && is_array($rule['and'])) {
            foreach ($rule['and'] as $sub) {
                if (! $this->evaluate($sub, $data)) {
                    return false;
                }
            }

            return true;
        }

        if (isset($rule['or']) && is_array($rule['or'])) {
            foreach ($rule['or'] as $sub) {
                if ($this->evaluate($sub, $data)) {
                    return true;
                }
            }

            return false;
        }

        $field = $rule['field'] ?? null;

        if ($field === null) {
            return true;
        }

        $actual = data_get($data, $field);
        $operator = $rule['operator'] ?? '=';
        $expected = $rule['value'] ?? null;

        return match ($operator) {
            '=' => $actual == $expected,
            '!=' => $actual != $expected,
            '>' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            '<' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            '>=' => is_numeric($actual) && is_numeric($expected) && (float) $actual >= (float) $expected,
            '<=' => is_numeric($actual) && is_numeric($expected) && (float) $actual <= (float) $expected,
            'contains' => is_string($actual) && str_contains($actual, (string) $expected),
            default => false,
        };
    }
}
