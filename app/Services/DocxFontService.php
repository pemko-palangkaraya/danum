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
    private const TABLE_FONT_SIZE_HALF_POINTS = 20;

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

            $inTable = $xpath->query('ancestor::w:tc', $run)->length > 0;
            $this->setFontProperty($dom, $properties, $font->value, $inTable ? self::TABLE_FONT_SIZE_HALF_POINTS : self::BODY_FONT_SIZE_HALF_POINTS);
        }

        return $dom->saveXML() ?: $xml;
    }

    private function setFontProperty(DOMDocument $dom, DOMElement $properties, string $font, int $size): void
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
        $this->setSizeProperty($dom, $properties, 'sz', $size);
        $this->setSizeProperty($dom, $properties, 'szCs', $size);
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
