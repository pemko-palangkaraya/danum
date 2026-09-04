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

    public function embed(string $docxPath, string $verificationUrl): void
    {
        if (! is_file($docxPath)) throw new RuntimeException('DOCX hasil surat tidak ditemukan.');

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) throw new RuntimeException('DOCX hasil surat tidak dapat dibuka.');

        $parts = $this->tteParts($zip);
        if ($parts === []) {
            $zip->close();
            return;
        }

        $svg = app(VerificationQrCodeService::class)->render($verificationUrl);
        $prefix = 'data:image/svg+xml;base64,';
        if (! str_starts_with($svg, $prefix)) {
            $zip->close();
            throw new RuntimeException('QR verification tidak menghasilkan SVG yang valid.');
        }
        $svgBytes = base64_decode(substr($svg, strlen($prefix)), true);
        if ($svgBytes === false) {
            $zip->close();
            throw new RuntimeException('Data QR verification tidak valid.');
        }

        $mediaName = 'danum-tte-' . substr(hash('sha256', $verificationUrl), 0, 16) . '.svg';
        $contentTypes = $zip->getFromName('[Content_Types].xml');
        if ($contentTypes === false) {
            $zip->close();
            throw new RuntimeException('DOCX [Content_Types].xml tidak ditemukan.');
        }

        $embedded = false;
        foreach ($parts as $part) {
            $xml = $zip->getFromName($part['xml']);
            $rels = $zip->getFromName($part['rels']);
            if ($xml === false || $rels === false) continue;

            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = true;
            if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                continue;
            }
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', self::WORD_NS);
            $node = $xpath->query('//w:t[contains(., "{{tte}}")]')?->item(0);
            if (! $node) continue;

            $rid = $this->nextRelationshipId($rels);
            $drawingXml = $this->drawingXml($rid, $mediaName);
            $fragment = $dom->createDocumentFragment();
            if (! $fragment->appendXML($drawingXml)) continue;
            $node->parentNode?->replaceChild($fragment, $node);

            $relsDom = new DOMDocument();
            $relsDom->preserveWhiteSpace = true;
            if (! $relsDom->loadXML($rels, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) continue;
            $root = $relsDom->documentElement;
            $rel = $relsDom->createElementNS(self::REL_NS, 'Relationship');
            $rel->setAttribute('Id', $rid);
            $rel->setAttribute('Type', self::OFFICE_REL_NS . '/image');
            $rel->setAttribute('Target', 'media/' . $mediaName);
            $root?->appendChild($rel);

            $zip->addFromString($part['xml'], $dom->saveXML() ?: $xml);
            $zip->addFromString($part['rels'], $relsDom->saveXML() ?: $rels);
            $embedded = true;
        }

        if ($embedded) {
            $zip->addFromString('[Content_Types].xml', $this->ensureSvgContentType($contentTypes));
            $zip->addFromString('word/media/' . $mediaName, $svgBytes);
        }
        $zip->close();
    }

    public function createIssuedCopy(string $sourcePath, string $verificationUrl): string
    {
        $copy = $this->temporaryCopy($sourcePath, 'danum-issued-');
        try { $this->embed($copy, $verificationUrl); } catch (\Throwable $e) { @unlink($copy); throw $e; }
        return $copy;
    }

    public function createPreviewCopy(string $sourcePath): string
    {
        $copy = $this->temporaryCopy($sourcePath, 'danum-preview-');
        try { $this->removeTte($copy); } catch (\Throwable $e) { @unlink($copy); throw $e; }
        return $copy;
    }

    private function tteParts(ZipArchive $zip): array
    {
        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || ! preg_match('#^word/(document|header\d+|footer\d+)\.xml$#', $name, $matches)) continue;
            $base = pathinfo($name, PATHINFO_FILENAME);
            $rels = 'word/_rels/' . $base . '.xml.rels';
            if ($zip->locateName($rels) !== false) $parts[] = ['xml' => $name, 'rels' => $rels];
        }
        return $parts;
    }

    private function nextRelationshipId(string $rels): string
    {
        $dom = new DOMDocument();
        $dom->loadXML($rels, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
        $used = [];
        foreach ($dom->documentElement?->childNodes ?? [] as $child) {
            if ($child instanceof DOMElement) $used[$child->getAttribute('Id')] = true;
        }
        $number = 1;
        do { $candidate = 'rIdDanumTte' . $number++; } while (isset($used[$candidate]));
        return $candidate;
    }

    private function drawingXml(string $rid, string $mediaName): string
    {
        $cx = 1050000;
        $cy = 1050000;
        return '<w:drawing xmlns:w="' . self::WORD_NS . '" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:r="' . self::OFFICE_REL_NS . '">'
            . '<wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="' . $cx . '" cy="' . $cy . '"/><wp:docPr id="9002" name="' . self::TTE_NAME . '"/>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="' . $mediaName . '"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="' . $rid . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing>';
    }

    private function ensureSvgContentType(string $contentTypes): string
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($contentTypes, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) return $contentTypes;
        foreach ($dom->getElementsByTagName('Default') as $item) {
            if (strcasecmp($item->getAttribute('Extension'), 'svg') === 0) return $dom->saveXML() ?: $contentTypes;
        }
        $default = $dom->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Default');
        $default->setAttribute('Extension', 'svg');
        $default->setAttribute('ContentType', 'image/svg+xml');
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

    private function removeTte(string $docxPath): void
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
            $xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
            $xpath->registerNamespace('w', self::WORD_NS);
            $drawings = $xpath->query('//wp:docPr[@name="' . self::TTE_NAME . '"]/parent::wp:inline/parent::w:drawing');
            if ($drawings) foreach ($drawings as $drawing) $drawing->parentNode?->removeChild($drawing);
            $zip->addFromString($part['xml'], $dom->saveXML() ?: $xml);
        }
        $zip->close();
    }
}
