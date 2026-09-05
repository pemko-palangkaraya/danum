<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class DocxTteService
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const OFFICE_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const TTE_NAME = 'DANUM TTE QR';
    private const QR_NAME = 'DANUM QR';
    private const TTE_MARKER = '{{tte}}';
    private const QR_MARKER = '{{qr}}';

    public function embed(string $docxPath, string $verificationUrl, string $marker = 'tte'): void
    {
        if (! is_file($docxPath)) throw new RuntimeException('DOCX hasil surat tidak ditemukan.');

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) throw new RuntimeException('DOCX hasil surat tidak dapat dibuka.');

        $markerConfig = $this->markerConfig($marker);
        $parts = $this->tteParts($zip);
        if ($parts === []) { $zip->close(); return; }

        $png = app(VerificationQrCodeService::class)->render($verificationUrl);
        $prefix = 'data:image/png;base64,';
        if (! str_starts_with($png, $prefix)) {
            $zip->close();
            throw new RuntimeException('QR verification tidak menghasilkan PNG yang valid.');
        }

        $pngBytes = base64_decode(substr($png, strlen($prefix)), true);
        if ($pngBytes === false) {
            $zip->close();
            throw new RuntimeException('Data QR verification tidak valid.');
        }

        $mediaName = 'danum-' . $markerConfig['media_prefix'] . '-' . substr(hash('sha256', $verificationUrl), 0, 16) . '.png';
        $contentTypes = $zip->getFromName('[Content_Types].xml');
        if ($contentTypes === false) { $zip->close(); throw new RuntimeException('DOCX [Content_Types].xml tidak ditemukan.'); }

        $embedded = false;
        foreach ($parts as $part) {
            $xml = $zip->getFromName($part['xml']);
            $rels = $zip->getFromName($part['rels']);
            if ($xml === false || $rels === false) continue;

            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = true;
            if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) continue;
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', self::WORD_NS);

            $replacement = $this->replaceMarkerInDocument($dom, $xpath, $rels, $mediaName, $markerConfig['marker'], $markerConfig['name']);
            if ($replacement === null) continue;

            [, $updatedRels] = $replacement;
            $zip->addFromString($part['xml'], $dom->saveXML() ?: $xml);
            $zip->addFromString($part['rels'], $updatedRels);
            $embedded = true;
        }

        if ($embedded) {
            $zip->addFromString('[Content_Types].xml', $this->ensurePngContentType($contentTypes));
            $zip->addFromString('word/media/' . $mediaName, $pngBytes);
        }
        $zip->close();
    }

    public function createIssuedCopy(string $sourcePath, string $verificationUrl, string $marker = 'tte'): string
    {
        $copy = $this->temporaryCopy($sourcePath, 'danum-issued-');
        try {
            $this->removeMarkers($copy, [$marker === 'tte' ? self::QR_MARKER : self::TTE_MARKER]);
            $this->embed($copy, $verificationUrl, $marker);
        } catch (\Throwable $e) { @unlink($copy); throw $e; }
        return $copy;
    }

    public function createPreviewCopy(string $sourcePath): string
    {
        $copy = $this->temporaryCopy($sourcePath, 'danum-preview-');
        try { $this->removeMarkers($copy, [self::TTE_MARKER, self::QR_MARKER]); } catch (\Throwable $e) { @unlink($copy); throw $e; }
        return $copy;
    }

    private function markerConfig(string $marker): array
    {
        return match ($marker) {
            'qr' => ['marker' => self::QR_MARKER, 'name' => self::QR_NAME, 'media_prefix' => 'qr'],
            'tte' => ['marker' => self::TTE_MARKER, 'name' => self::TTE_NAME, 'media_prefix' => 'tte'],
            default => throw new RuntimeException('Marker dokumen TTE tidak valid.'),
        };
    }

    private function tteParts(ZipArchive $zip): array
    {
        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || ! preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $name)) continue;
            $base = pathinfo($name, PATHINFO_FILENAME);
            $rels = 'word/_rels/' . $base . '.xml.rels';
            if (zip_entry_exists($zip, $rels)) $parts[] = ['xml' => $name, 'rels' => $rels];
        }
        return $parts;
    }

    private function replaceMarkerInDocument(DOMDocument $dom, DOMXPath $xpath, string $rels, string $mediaName, string $marker, string $name): ?array
    {
        $paragraphs = $xpath->query('//w:p');
        if (! $paragraphs) return null;

        foreach ($paragraphs as $paragraph) {
            if (! $paragraph instanceof DOMElement) continue;
            $nodes = $xpath->query('.//w:t', $paragraph);
            if (! $nodes || $nodes->length === 0) continue;

            $text = '';
            $items = [];
            foreach ($nodes as $node) {
                if (! $node instanceof DOMElement) continue;
                $value = $node->textContent;
                $start = strlen($text);
                $text .= $value;
                $items[] = ['node' => $node, 'start' => $start, 'length' => strlen($value)];
            }

            $markerStart = strpos($text, $marker);
            if ($markerStart === false) continue;
            $markerEnd = $markerStart + strlen($marker);

            $first = null;
            $last = null;
            foreach ($items as $item) {
                $itemEnd = $item['start'] + $item['length'];
                if ($first === null && $markerStart < $itemEnd) $first = $item;
                if ($markerEnd > $item['start'] && $markerEnd <= $itemEnd) { $last = $item; break; }
            }
            if ($first === null || $last === null) continue;

            $rid = $this->nextRelationshipId($rels);
            $drawingXml = $this->drawingXml($rid, $mediaName, $name);
            $fragment = $dom->createDocumentFragment();
            if (! $fragment->appendXML($drawingXml)) continue;

            $firstNode = $first['node'];
            $firstOffset = $markerStart - $first['start'];
            $prefix = substr($firstNode->textContent, 0, max(0, $firstOffset));
            $suffix = '';
            if ($firstNode === $last['node']) {
                $after = $markerEnd - $first['start'];
                $suffix = substr($firstNode->textContent, $after);
            } else {
                $after = $markerEnd - $last['start'];
                $suffix = substr($last['node']->textContent, $after);
            }

            $firstNode->nodeValue = $prefix;
            $firstNode->parentNode?->appendChild($fragment);

            foreach ($items as $item) {
                $node = $item['node'];
                if ($node === $firstNode) continue;
                $itemEnd = $item['start'] + $item['length'];
                if ($item['start'] >= $markerStart && $itemEnd <= $markerEnd) {
                    $node->nodeValue = '';
                } elseif ($node === $last['node']) {
                    $node->nodeValue = $suffix;
                }
            }

            $relsDom = new DOMDocument();
            $relsDom->preserveWhiteSpace = true;
            if (! $relsDom->loadXML($rels, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) continue;
            $root = $relsDom->documentElement;
            $rel = $relsDom->createElementNS(self::REL_NS, 'Relationship');
            $rel->setAttribute('Id', $rid);
            $rel->setAttribute('Type', self::OFFICE_REL_NS . '/image');
            $rel->setAttribute('Target', 'media/' . $mediaName);
            $root?->appendChild($rel);

            return [$rid, $relsDom->saveXML() ?: $rels];
        }

        return null;
    }

    private function nextRelationshipId(string $rels): string
    {
        $dom = new DOMDocument();
        if (! $dom->loadXML($rels, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) return 'rIdDanumTte1';
        $used = [];
        foreach ($dom->documentElement?->childNodes ?? [] as $child) {
            if ($child instanceof DOMElement) $used[$child->getAttribute('Id')] = true;
        }
        $number = 1;
        do { $candidate = 'rIdDanumTte' . $number++; } while (isset($used[$candidate]));
        return $candidate;
    }

    private function drawingXml(string $rid, string $mediaName, string $name): string
    {
        $cx = 1050000;
        $cy = 1050000;
        return '<w:drawing xmlns:w="' . self::WORD_NS . '" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:r="' . self::OFFICE_REL_NS . '">'
            . '<wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="' . $cx . '" cy="' . $cy . '"/><wp:docPr id="9002" name="' . $name . '"/>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="' . $mediaName . '"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="' . $rid . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing>';
    }

    private function ensurePngContentType(string $contentTypes): string
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($contentTypes, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) return $contentTypes;
        foreach ($dom->getElementsByTagName('Default') as $item) {
            if (strcasecmp($item->getAttribute('Extension'), 'png') === 0) return $dom->saveXML() ?: $contentTypes;
        }
        $default = $dom->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Default');
        $default->setAttribute('Extension', 'png');
        $default->setAttribute('ContentType', 'image/png');
        $dom->documentElement?->appendChild($default);
        return $dom->saveXML() ?: $contentTypes;
    }

    private function temporaryCopy(string $sourcePath, string $prefix): string
    {
        if (! is_file($sourcePath)) throw new RuntimeException('DOCX hasil surat tidak ditemukan.');
        $tmp = tempnam(sys_get_temp_dir(), $prefix);
        if ($tmp === false || ! copy($sourcePath, $tmp)) { if ($tmp !== false) @unlink($tmp); throw new RuntimeException('Tidak dapat membuat salinan DOCX sementara.'); }
        return $tmp;
    }

    private function removeMarkers(string $docxPath, array $markers): void
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) throw new RuntimeException('DOCX hasil surat tidak dapat dibuka.');
        foreach ($this->tteParts($zip) as $part) {
            $xml = $zip->getFromName($part['xml']);
            if ($xml === false) continue;
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = true;
            if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) continue;
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', self::WORD_NS);
            $xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');

            foreach ($markers as $marker) {
                foreach ($xpath->query('//w:t[contains(., "' . $marker . '")]') ?: [] as $node) $node->nodeValue = str_replace($marker, '', $node->textContent);
            }

            foreach ([self::TTE_NAME, self::QR_NAME] as $name) {
                $drawings = $xpath->query('//wp:docPr[@name="' . $name . '"]/parent::wp:inline/parent::w:drawing');
                if ($drawings) foreach ($drawings as $drawing) $drawing->parentNode?->removeChild($drawing);
            }

            $zip->addFromString($part['xml'], $dom->saveXML() ?: $xml);
        }
        $zip->close();
    }
}

function zip_entry_exists(ZipArchive $zip, string $name): bool
{
    return $zip->locateName($name) !== false;
}
