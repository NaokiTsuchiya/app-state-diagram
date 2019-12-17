<?php

declare(strict_types=1);

namespace Koriym\AlpsStateDiagram;

use Koriym\AlpsStateDiagram\Exception\AlpsFileNotReadableException;
use Koriym\AlpsStateDiagram\Exception\DescriptorNotFoundException;
use Koriym\AlpsStateDiagram\Exception\InvalidAlpsException;
use Koriym\AlpsStateDiagram\Exception\InvalidJsonException;

final class AlpsStateDiagram
{
    /**
     * @var array
     */
    private $links = [];

    /**
     * @var string
     */
    private $dir = '';

    /**
     * @var SemanticScanner
     */
    private $scanner;

    /**
     * @var array
     */
    private $semantics = [];

    public function __construct()
    {
        $this->scanner = new SemanticScanner;
    }

    public function __invoke(string $alpsFile) : string
    {
        $this->dir = dirname($alpsFile);
        $descriptors = $this->scanAlpsFile($alpsFile);
        foreach ($descriptors as $descriptor) {
            $this->scanDescriptor($descriptor);
        }

        return $this->toString();
    }

    private function scanDescriptor(\stdClass $descriptor) : void
    {
        if (isset($descriptor->descriptor)) {
            $this->scanTransition(new SemanticDescriptor($descriptor), $descriptor->descriptor);

            return;
        }
        if (isset($descriptor->href)) {
            $this->href($descriptor);
        }
    }

    private function href(\stdClass $descriptor) : void
    {
        $isExternal = $descriptor->href[0] !== '#';
        if ($isExternal) {
            $this->scanDescriptor($this->getExternDescriptor($descriptor->href));

            return;
        }
    }

    private function scanTransition(SemanticDescriptor $semantic, array $descriptors) : void
    {
        foreach ($descriptors as $descriptor) {
            $isExternal = isset($descriptor->href) && $descriptor->href[0] !== '#';
            if ($isExternal) {
                $descriptor = $this->getExternDescriptor($descriptor->href);
            }
            $isInternal = isset($descriptor->href) && $descriptor->href[0] === '#';
            if ($isInternal) {
                $this->addInternalLink($semantic, $descriptor->href);

                continue;
            }
            $isTransDescriptor = isset($descriptor->type) && in_array($descriptor->type, ['safe', 'unsafe', 'idempotent'], true);
            if ($isTransDescriptor) {
                $this->addLink(new Link($semantic, new TransDescriptor($descriptor, $semantic)));

                continue;
            }
        }
    }

    private function addInternalLink(SemanticDescriptor $semantic, string $href) : void
    {
        [,$descriptorId] = explode('#', $href);
        $isTransDescrpitor = isset($this->semantics[$descriptorId]) && $this->semantics[$descriptorId] instanceof TransDescriptor;
        if ($isTransDescrpitor) {
            $transSemantic = $this->semantics[$descriptorId];
            $this->addLink(new Link($semantic, $transSemantic));
        }
    }

    private function getExternDescriptor(string $href) : \stdClass
    {
        [$file, $descriptorId] = explode('#', $href);
        $descriptors = $this->scanAlpsFile("{$this->dir}/{$file}");

        return $this->getDescriptor($descriptors, $descriptorId, $href);
    }

    private function getDescriptor(array $descriptors, string $descriptorId, string $href) : \stdClass
    {
        foreach ($descriptors as $descriptor) {
            if ($descriptor->id === $descriptorId) {
                return $descriptor;
            }
        }

        throw new DescriptorNotFoundException($href);
    }

    private function addLink(Link $link) : void
    {
        $fromTo = sprintf('%s->%s', $link->from, $link->to);
        $this->links[$fromTo] = isset($this->links[$fromTo]) ? $this->links[$fromTo] . ', ' . $link->label : $link->label;
    }

    private function toString() : string
    {
        $graphs = '';
        foreach ($this->links as $link => $label) {
            $graphs .= sprintf('    %s [label = "%s"];', $link, $label) . PHP_EOL;
        }

        return sprintf('digraph application_state_diagram {
    node [shape = box, style = "bold,filled"];
%s
}
', $graphs);
    }

    private function scanAlpsFile(string $alpsFile) : array
    {
        if (! file_exists($alpsFile)) {
            throw new AlpsFileNotReadableException($alpsFile);
        }
        $alps = json_decode((string) file_get_contents($alpsFile));
        $jsonError = json_last_error();
        if ($jsonError) {
            throw new InvalidJsonException($alpsFile);
        }
        if (! isset($alps->alps->descriptor)) {
            throw new InvalidAlpsException($alpsFile);
        }
        $this->semantics = array_merge($this->semantics, ($this->scanner)($alps->alps->descriptor));

        return $alps->alps->descriptor;
    }
}
