<?php

declare(strict_types=1);

namespace Koriym\AppStateDiagram;

use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    /** @var AlpsProfile */
    private $profile;

    protected function setUp(): void
    {
        $this->profile = new AlpsProfile(__DIR__ . '/Fake/alps_tag.json');
    }

    public function testFilteredByAndTag(): void
    {
        $profile = (new TaggedAlpsProfile(
            $this->profile,
            [],
            ['a', 'b']
        ));
        $this->assertArrayHasKey('s1->s2:t1', $profile->links);
        $this->assertArrayNotHasKey('s2->s3:t2', $profile->links);
    }

    public function testFilteredByOrTag(): void
    {
        $profile = (new TaggedAlpsProfile(
            $this->profile,
            ['a'],
            []
        ));
        $this->assertArrayHasKey('s1->s2:t1', $profile->links);
        $this->assertArrayHasKey('s2->s3:t2', $profile->links);
        $this->assertArrayHasKey('s3->s4:t3', $profile->links);
    }
}
