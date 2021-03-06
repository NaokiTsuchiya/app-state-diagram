<?php

declare(strict_types=1);

namespace Koriym\AppStateDiagram;

use PHPUnit\Framework\TestCase;

class IndexPageTest extends TestCase
{
    public function testInvoke(): void
    {
        $alpsFile = __DIR__ . '/Fake/alps.json';
        $html = (new IndexPage(new AlpsProfile($alpsFile)))->index;
        $this->assertStringContainsString('<li><a href="docs/semantic.About.html">About</a> (semantic)</li>', $html);
    }

    public function testTagString(): void
    {
        $alpsFile = __DIR__ . '/Fake/alps_tag.json';
        $html = (new IndexPage(new AlpsProfile($alpsFile)))->index;
        $this->assertStringContainsString('<li><a href="docs/tag.a.html">a</a>', $html);
        $this->assertStringContainsString('<li><a href="docs/tag.b.html">b</a>', $html);
    }
}
