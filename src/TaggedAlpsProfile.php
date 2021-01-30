<?php

declare(strict_types=1);

namespace Koriym\AppStateDiagram;

use function array_key_exists;
use function in_array;

final class TaggedAlpsProfile extends AbstractProfile
{
    /**
     * @param list<string> $orTags
     * @param list<string> $andTags
     */
    public function __construct(AbstractProfile $alpsFile, array $orTags, array $andTags)
    {
        $descriptors = new Descriptors();
        foreach ($alpsFile->descriptors as $descriptor) {
            if ($this->isFilteredAnd($descriptor, $andTags)) {
                $descriptors->add($descriptor);
            }

            if ($this->isFilteredOr($descriptor, $orTags)) {
                $descriptors->add($descriptor);
            }
        }

        $this->descriptors = $descriptors->descriptors;

        $links = new Links();
        foreach ($alpsFile->links as $link) {
            if (! $this->hasFromTo($link)) {
                continue;
            }

            if ($this->isFilteredAnd($link->transDescriptor, $andTags)) {
                $links->add($link);
            }

            if ($this->isFilteredOr($link->transDescriptor, $orTags)) {
                $links->add($link);
            }
        }

        $this->links = $links->links;
    }

    /**
     * @param list<string> $andTags
     */
    private function isFilteredAnd(AbstractDescriptor $descriptor, array $andTags): bool
    {
        foreach ($andTags as $tag) {
            if (! in_array($tag, $descriptor->tags, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<string> $orTags
     */
    private function isFilteredOr(AbstractDescriptor $descriptor, array $orTags): bool
    {
        foreach ($orTags as $tag) {
            if (in_array($tag, $descriptor->tags, true)) {
                return true;
            }
        }

        return false;
    }

    private function hasFromTo(Link $link): bool
    {
        return array_key_exists($link->from, $this->descriptors) && array_key_exists($link->to, $this->descriptors);
    }
}
