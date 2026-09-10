<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LetterFont;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

final class DocxFontService
{
    private const BODY_FONT_SIZE_HALF_POINTS = 24;

    public function apply(string $xml, LetterFont $font): string
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new RuntimeException('DOCX document.xml tidak valid.');

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', DocxRendererService::WORD_NS);
        $runs = $xpath->query('//w:r');
        if ($runs) foreach ($runs as $run) {
            if (! $run instanceof DOMElement) continue;
            $properties = null;
            foreach ($run->childNodes as $child) {
                if ($child instanceof DOMElement && $child->localName === 'rPr') { $properties = $child; break; }
            }
            if (! $properties) {
                $properties = $dom->createElementNS(DocxRendererService::WORD_NS, 'w:rPr');
                $run->insertBefore($properties, $run->firstChild);
            }
            $this->setFontProperty($dom, $properties, $font->value);
        }

        return $dom->saveXML() ?: $xml;
    }

    private function setFontProperty(DOMDocument $dom, DOMElement $properties, string $font): void
    {
        $fontNodes = [];
        foreach ($properties->childNodes as $child) if ($child instanceof DOMElement && $child->localName === 'rFonts') $fontNodes[] = $child;
        $fonts = $fontNodes[0] ?? null;
        if (! $fonts) {
            $fonts = $dom->createElementNS(DocxRendererService::WORD_NS, 'w:rFonts');
            $properties->insertBefore($fonts, $properties->firstChild);
        }
        foreach ($fontNodes as $index => $node) if ($index > 0) $properties->removeChild($node);
        $fonts->setAttributeNS(DocxRendererService::WORD_NS, 'w:ascii', $font);
        $fonts->setAttributeNS(DocxRendererService::WORD_NS, 'w:hAnsi', $font);
        $fonts->setAttributeNS(DocxRendererService::WORD_NS, 'w:cs', $font);
        $fonts->setAttributeNS(DocxRendererService::WORD_NS, 'w:eastAsia', $font);
        $this->setSizeProperty($dom, $properties, 'sz', self::BODY_FONT_SIZE_HALF_POINTS);
        $this->setSizeProperty($dom, $properties, 'szCs', self::BODY_FONT_SIZE_HALF_POINTS);
    }

    private function setSizeProperty(DOMDocument $dom, DOMElement $properties, string $name, int $size): void
    {
        $node = null;
        foreach ($properties->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name) { $node = $child; break; }
        }
        if (! $node) {
            $node = $dom->createElementNS(DocxRendererService::WORD_NS, 'w:' . $name);
            $properties->appendChild($node);
        }
        $node->setAttributeNS(DocxRendererService::WORD_NS, 'w:val', (string) $size);
    }
}
