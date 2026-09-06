<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

class DocxRendererService
{
    public const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** @param array<string,mixed> $values */
    public function render(string $xml, array $values): string
    {
        $xml = $this->renderRepeaters($xml, $values);
        return $this->replacePlaceholders($xml, $values);
    }

    /** @param array<string,mixed> $values */
    private function renderRepeaters(string $xml, array $values): string
    {
        $dom = $this->loadDocument($xml);
        $xpath = $this->xpath($dom);
        $rows = $xpath->query('//w:tr');

        if ($rows) foreach (iterator_to_array($rows) as $row) {
            $text = $this->nodeText($xpath, $row);
            if (! preg_match('/\{\{#\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}(.*?)\{\{\/\s*\1\s*\}\}/s', $text, $match)) continue;
            $items = is_array($values[$match[1]] ?? null) ? $values[$match[1]] : [];
            $parent = $row->parentNode;
            if (! $parent) continue;

            foreach ($items as $item) {
                if (! is_array($item)) continue;
                $clone = $row->cloneNode(true);
                $this->replaceRepeatMarkers($xpath, $clone, $match[1], $item);
                $parent->insertBefore($clone, $row);
            }
            $parent->removeChild($row);
        }

        $paragraphs = $xpath->query('//w:p');
        if ($paragraphs) foreach (iterator_to_array($paragraphs) as $paragraph) {
            $text = $this->nodeText($xpath, $paragraph);
            if (! preg_match('/^\s*\{\{#\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}\s*$/', $text, $start)) continue;
            $key = $start[1];
            $end = $paragraph->nextSibling;
            $block = [];

            while ($end) {
                if (preg_match('/^\s*\{\{\/\s*' . preg_quote($key, '/') . '\s*\}\}\s*$/', $this->nodeText($xpath, $end))) break;
                $block[] = $end;
                $end = $end->nextSibling;
            }
            if (! $end) continue;

            $items = is_array($values[$key] ?? null) ? $values[$key] : [];
            $container = $paragraph->parentNode;
            if (! $container) continue;

            foreach ($items as $item) {
                if (! is_array($item)) continue;
                foreach ($block as $sourceNode) {
                    $clone = $sourceNode->cloneNode(true);
                    $this->replaceRepeatMarkers($xpath, $clone, $key, $item);
                    $container->insertBefore($clone, $end);
                }
            }

            $container->removeChild($paragraph);
            foreach ($block as $sourceNode) $container->removeChild($sourceNode);
            $container->removeChild($end);
        }

        return $dom->saveXML() ?: $xml;
    }

    /** @param array<string,mixed> $item */
    private function replaceRepeatMarkers(DOMXPath $xpath, \DOMNode $node, string $key, array $item): void
    {
        $nodes = $xpath->query('.//w:t', $node);
        if (! $nodes) return;

        foreach ($nodes as $textNode) {
            $value = preg_replace_callback(
                '/\{\{#\s*' . preg_quote($key, '/') . '\s*\}\}|\{\{\/\s*' . preg_quote($key, '/') . '\s*\}\}|\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/',
                static function ($match) use ($item): string {
                    if (isset($match[1]) && $match[1] !== '') {
                        return htmlspecialchars((string) ($item[$match[1]] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    }
                    return '';
                },
                $textNode->textContent,
            );
            $textNode->nodeValue = $value ?? $textNode->textContent;
        }
    }

    private function nodeText(DOMXPath $xpath, \DOMNode $node): string
    {
        $nodes = $xpath->query('.//w:t', $node);
        $text = '';
        if ($nodes) foreach ($nodes as $textNode) $text .= $textNode->textContent;
        return $text;
    }

    /** @param array<string,mixed> $values */
    private function replacePlaceholders(string $xml, array $values): string
    {
        $dom = $this->loadDocument($xml);
        $xpath = $this->xpath($dom);
        $paragraphs = $xpath->query('//w:p');
        if (! $paragraphs) return $xml;

        foreach ($paragraphs as $paragraph) {
            if (! $paragraph instanceof DOMElement) continue;
            $nodes = $xpath->query('.//w:t', $paragraph);
            if (! $nodes || $nodes->length === 0) continue;

            $items = [];
            $text = '';
            foreach ($nodes as $node) {
                if (! $node instanceof DOMElement) continue;
                $value = $node->textContent;
                $start = strlen($text);
                $text .= $value;
                $items[] = ['node' => $node, 'start' => $start, 'length' => strlen($value)];
            }
            if ($text === '') continue;

            preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_.]*)\s*\}\}/', $text, $matches, PREG_OFFSET_CAPTURE);
            if (empty($matches[0])) continue;

            foreach (array_reverse($matches[0]) as $match) {
                $placeholder = $match[0];
                $matchStart = $match[1];
                $matchEnd = $matchStart + strlen($placeholder);
                if (! preg_match('/^\{\{\s*([A-Za-z_][A-Za-z0-9_.]*)\s*\}\}$/', $placeholder, $parts)) continue;
                $key = $parts[1];
                if (in_array($key, ['letterhead', 'qr', 'tte'], true)) continue;

                $replacement = htmlspecialchars((string) ($values[$key] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $firstIndex = null;
                $lastIndex = null;

                foreach ($items as $index => $item) {
                    $itemEnd = $item['start'] + $item['length'];
                    if ($firstIndex === null && $matchStart < $itemEnd && $matchEnd > $item['start']) $firstIndex = $index;
                    if ($matchEnd > $item['start'] && $matchEnd <= $itemEnd) {
                        $lastIndex = $index;
                        break;
                    }
                }
                if ($firstIndex === null || $lastIndex === null) continue;

                $firstNode = $items[$firstIndex]['node'];
                $firstText = $firstNode->textContent;
                $firstOffset = $matchStart - $items[$firstIndex]['start'];
                $prefix = substr($firstText, 0, max(0, $firstOffset));

                if ($firstIndex === $lastIndex) {
                    $after = $matchEnd - $items[$lastIndex]['start'];
                    $suffix = substr($firstText, $after);
                    $firstNode->nodeValue = $prefix . $replacement . $suffix;
                    continue;
                }

                $lastNode = $items[$lastIndex]['node'];
                $after = $matchEnd - $items[$lastIndex]['start'];
                $suffix = substr($lastNode->textContent, $after);
                $firstNode->nodeValue = $prefix . $replacement;
                for ($index = $firstIndex + 1; $index < $lastIndex; $index++) $items[$index]['node']->nodeValue = '';
                $lastNode->nodeValue = $suffix;
            }
        }

        return $dom->saveXML() ?: $xml;
    }

    private function loadDocument(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            throw new RuntimeException('DOCX document.xml tidak valid.');
        }
        return $dom;
    }

    private function xpath(DOMDocument $dom): DOMXPath
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);
        return $xpath;
    }
}
