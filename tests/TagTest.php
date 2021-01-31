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
        $this->assertArrayHasKey('s1->s5:t5', $profile->links);
        $this->assertArrayHasKey('s2->s3:t2', $profile->links);
        $this->assertArrayNotHasKey('s2->s4:t4', $profile->links);
        $this->assertArrayNotHasKey('s3->s4:t3', $profile->links);
        $this->assertArrayNotHasKey('s5->s6:s6', $profile->links);
        $this->assertArrayHasKey('s1', $profile->descriptors);
        $this->assertArrayHasKey('s2', $profile->descriptors);
        $this->assertArrayHasKey('s3', $profile->descriptors);
        $this->assertArrayNotHasKey('s4', $profile->descriptors);
        $this->assertArrayHasKey('s5', $profile->descriptors);
        $this->assertArrayNotHasKey('s6', $profile->descriptors);
        $this->assertArrayHasKey('t1', $profile->descriptors);
        $this->assertArrayHasKey('t2', $profile->descriptors);
        $this->assertArrayNotHasKey('t3', $profile->descriptors);
        $this->assertArrayNotHasKey('t4', $profile->descriptors);
        $this->assertArrayHasKey('t5', $profile->descriptors);
        $this->assertArrayNotHasKey('s6', $profile->descriptors);
        $this->assertArrayHasKey('id', $profile->descriptors);
        $this->assertCount(3, $profile->descriptors['s1']->descriptor);
        $this->assertCount(1, $profile->descriptors['s2']->descriptor);
    }

    public function testFilteredByOrTag(): void
    {
        $profile = (new TaggedAlpsProfile(
            $this->profile,
            ['a'],
            []
        ));
        $this->assertArrayHasKey('s1->s2:t1', $profile->links);
        $this->assertArrayHasKey('s1->s5:t5', $profile->links);
        $this->assertArrayHasKey('s2->s3:t2', $profile->links);
        $this->assertArrayNotHasKey('s2->s4:t4', $profile->links);
        $this->assertArrayHasKey('s3->s4:t3', $profile->links);
        $this->assertArrayHasKey('s5->s6:t6', $profile->links);
        $this->assertArrayHasKey('s1', $profile->descriptors);
        $this->assertArrayHasKey('s2', $profile->descriptors);
        $this->assertArrayHasKey('s3', $profile->descriptors);
        $this->assertArrayHasKey('s4', $profile->descriptors);
        $this->assertArrayHasKey('s5', $profile->descriptors);
        $this->assertArrayHasKey('s6', $profile->descriptors);
        $this->assertArrayHasKey('t1', $profile->descriptors);
        $this->assertArrayHasKey('t2', $profile->descriptors);
        $this->assertArrayHasKey('t3', $profile->descriptors);
        $this->assertArrayNotHasKey('t4', $profile->descriptors);
        $this->assertArrayHasKey('t5', $profile->descriptors);
        $this->assertArrayHasKey('s6', $profile->descriptors);
        $this->assertArrayHasKey('id', $profile->descriptors);
        $this->assertCount(3, $profile->descriptors['s1']->descriptor);
        $this->assertCount(1, $profile->descriptors['s2']->descriptor);
    }
}
