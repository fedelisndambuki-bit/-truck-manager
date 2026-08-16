<?php

namespace Tests\Unit;

use App\Support\SimpleMarkdown;
use Tests\TestCase;

class SimpleMarkdownTest extends TestCase
{
    public function test_renders_a_heading(): void
    {
        $html = SimpleMarkdown::toHtml('### Summary');

        $this->assertStringContainsString('<strong class="tm-ai-heading">Summary</strong>', $html);
    }

    public function test_renders_bold_inline(): void
    {
        $html = SimpleMarkdown::toHtml('This is **important**.');

        $this->assertStringContainsString('<strong>important</strong>', $html);
    }

    public function test_renders_a_bulleted_list(): void
    {
        $html = SimpleMarkdown::toHtml("- First\n- Second");

        $this->assertStringContainsString('<ul><li>First</li><li>Second</li></ul>', $html);
    }

    public function test_renders_a_numbered_list(): void
    {
        $html = SimpleMarkdown::toHtml("1. First\n2. Second");

        $this->assertStringContainsString('<ol><li>First</li><li>Second</li></ol>', $html);
    }

    public function test_escapes_html_to_prevent_injection(): void
    {
        $html = SimpleMarkdown::toHtml('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_plain_paragraph_is_wrapped(): void
    {
        $html = SimpleMarkdown::toHtml('Just a plain sentence.');

        $this->assertSame('<p>Just a plain sentence.</p>', $html);
    }
}
