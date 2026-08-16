<?php

namespace App\Support;

/**
 * Minimal, safe renderer for the small set of Markdown-ish patterns the AI
 * system prompt (AIService::getSystemPrompt()) actually asks the model to
 * produce: "### Heading" section titles, **bold**, and "- "/"1. " lists.
 * Deliberately not a general-purpose Markdown parser (no tables, links,
 * code blocks, images) — the chat bubble is narrow and this only needs to
 * handle exactly what the prompt requests. HTML-escapes the input FIRST,
 * so nothing the model writes can inject markup.
 */
class SimpleMarkdown
{
    public static function toHtml(string $text): string
    {
        $escaped = e($text);
        $lines = preg_split('/\r\n|\r|\n/', $escaped) ?: [];

        $html = [];
        $listType = null; // null | 'ul' | 'ol'

        $closeList = function () use (&$html, &$listType) {
            if ($listType !== null) {
                $html[] = "</{$listType}>";
                $listType = null;
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $closeList();

                continue;
            }

            if (preg_match('/^#{1,6}\s+(.*)$/', $trimmed, $m)) {
                $closeList();
                $html[] = '<strong class="tm-ai-heading">'.self::inline($m[1]).'</strong>';

                continue;
            }

            if (preg_match('/^\d+\.\s+(.*)$/', $trimmed, $m)) {
                if ($listType !== 'ol') {
                    $closeList();
                    $html[] = '<ol>';
                    $listType = 'ol';
                }
                $html[] = '<li>'.self::inline($m[1]).'</li>';

                continue;
            }

            if (preg_match('/^[-*]\s+(.*)$/', $trimmed, $m)) {
                if ($listType !== 'ul') {
                    $closeList();
                    $html[] = '<ul>';
                    $listType = 'ul';
                }
                $html[] = '<li>'.self::inline($m[1]).'</li>';

                continue;
            }

            $closeList();
            $html[] = '<p>'.self::inline($trimmed).'</p>';
        }

        $closeList();

        return implode('', $html);
    }

    private static function inline(string $text): string
    {
        return preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text) ?? $text;
    }
}
