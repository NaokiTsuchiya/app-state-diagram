<?php

declare(strict_types=1);

namespace Koriym\AppStateDiagram;

use stdClass;

use function in_array;
use function substr;

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

        $links = new Links();
        foreach ($alpsFile->links as $link) {
            if (! ($descriptors->has($link->from) && $descriptors->has($link->to))) {
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

        $filteredDescriptors = $descriptors->descriptors;

        $d = new Descriptors();

        foreach ($filteredDescriptors as $descriptor) {
            $children = $descriptor->descriptor;
            foreach ($children as $key => $child) {
                $descriptorId = $this->getDescriptorId($child);

                if (isset($filteredDescriptors[$descriptorId])) {
                    continue;
                }

                $target = $alpsFile->descriptors[$descriptorId];
                if ($target instanceof SemanticDescriptor) {
                    $d->add($target);
                    continue;
                }

                if ($target instanceof TransDescriptor) {
                    unset($descriptor->descriptor[$key]);
                    continue;
                }
            }
        }

        foreach ($d->descriptors as $descriptor) {
            $descriptors->add($descriptor);
        }

        $this->descriptors = $descriptors->descriptors;
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

    private function getDescriptorId(stdClass $child): string
    {
        return $child->id ?? substr($child->href, 1);
    }
}
