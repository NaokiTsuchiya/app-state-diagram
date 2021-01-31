<?php

declare(strict_types=1);

namespace Koriym\AppStateDiagram;

use Koriym\AppStateDiagram\Exception\InvalidDescriptorMissingIdOrHrefException;
use stdClass;

use function array_key_exists;
use function explode;
use function in_array;
use function json_encode;
use function substr;

final class TaggedAlpsProfile extends AbstractProfile
{
    /** @var array<string, AbstractDescriptor> */
    private $tranceDescriptor;

    /**
     * @param list<string> $orTags
     * @param list<string> $andTags
     */
    public function __construct(AbstractProfile $alpsFile, array $orTags, array $andTags)
    {
        $links = new Links();
        $transDescriptors = new Descriptors();
        foreach ($alpsFile->links as $link) {
            if ($this->isFilteredAnd($link->transDescriptor, $andTags)) {
                $links->add($link);
                $transDescriptors->add($link->transDescriptor);
                continue;
            }

            if ($this->isFilteredOr($link->transDescriptor, $orTags)) {
                $links->add($link);
                $transDescriptors->add($link->transDescriptor);
            }
        }

        $this->links = $links->links;
        $this->tranceDescriptor = $transDescriptors->descriptors;

        $descriptors = new Descriptors();
        foreach ($this->links as $link) {
            $descriptors->add($link->transDescriptor);
            $from = $this->filteredDescriptor(
                $link->from,
                $alpsFile->descriptors
            );
            foreach ($from->descriptors as $descriptor) {
                $descriptors->add($descriptor);
            }

            $to = $this->filteredDescriptor(
                $link->to,
                $alpsFile->descriptors
            );
            foreach ($to->descriptors as $descriptor) {
                $descriptors->add($descriptor);
            }
        }

        $this->descriptors = $descriptors->descriptors;
    }

    /**
     * @param list<string> $andTags
     */
    private function isFilteredAnd(AbstractDescriptor $descriptor, array $andTags): bool
    {
        if ($andTags === []) {
            return false;
        }

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
        if ($orTags === []) {
            return false;
        }

        foreach ($orTags as $tag) {
            if (in_array($tag, $descriptor->tags, true)) {
                return true;
            }
        }

        return false;
    }

    private function getDescriptorId(stdClass $child): string
    {
        if (isset($child->id)) {
            return $child->id;
        }

        $href = $child->href;

        if (! isset($href)) {
            throw new InvalidDescriptorMissingIdOrHrefException((string) json_encode($child));
        }

        $isInternal = $href[0] === '#';

        if ($isInternal) {
            return substr($href, 1);
        }

        [, $id] = explode('#', $href);

        return $id;
    }

    /**
     * @param array<string, AbstractDescriptor> $allDescriptors
     */
    private function filteredDescriptor(string $id, array $allDescriptors): Descriptors
    {
        $descriptors = new Descriptors();
        $from = $allDescriptors[$id];
        $filteredChildren = [];

        foreach ($from->descriptor as $child) {
            $descriptorId = $this->getDescriptorId($child);
            $descriptor = $allDescriptors[$descriptorId];
            if ($this->validDescriptor($descriptor)) {
                $filteredChildren[] = $child;
                $descriptors->add($descriptor);
                continue;
            }
        }

        $from->descriptor = $filteredChildren;
        $descriptors->add($from);

        return $descriptors;
    }

    private function validDescriptor(AbstractDescriptor $descriptor): bool
    {
        if ($descriptor instanceof SemanticDescriptor) {
            return true;
        }

        return $descriptor instanceof TransDescriptor && array_key_exists($descriptor->id, $this->tranceDescriptor);
    }
}
