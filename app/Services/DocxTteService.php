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
    public function embed(string $docxPath, string $verificationUrl): void
    {
        if (! is_file($docxPath)) throw new RuntimeException('DOCX hasil surat tidak ditemukan.');

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) throw new RuntimeException('DOCX hasil surat tidak dapat dibuka.');
        $xml = $zip->getFromName('word/document.xml');
        $rels = $zip->getFromName('word/_rels/document.xml.rels');
        $contentTypes = $zip->getFromName('[Content_Types].xml');
        if ($xml === false || $rels === false || $contentTypes === false) { $zip->close(); throw new RuntimeException('Struktur DOCX tidak lengkap.'); }

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) { $zip->close(); throw new RuntimeException('DOCX document.xml tidak valid.'); }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $node = $xpath->query('//w:t[contains(., "{{tte}}")]')?->item(0);
        if (! $node) { $zip->close(); return; }

        $svg = app(VerificationQrCodeService::class)->render($verificationUrl);
        $prefix = 'data:image/svg+xml;base64,';
        if (! str_starts_with($svg, $prefix)) { $zip->close(); throw new RuntimeException('QR verification tidak menghasilkan SVG yang valid.'); }
        $svgBytes = base64_decode(substr($svg, strlen($prefix)), true);
        if ($svgBytes === false) { $zip->close(); throw new RuntimeException('Data QR verification tidak valid.'); }

        $rid = 'rIdDanumTte';
        $mediaName = 'danum-tte-' . substr(hash('sha256', $verificationUrl), 0, 16) . '.svg';
        $cx = 1050000;
        $cy = 1050000;
        $drawingXml = '<w:drawing xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="'.$cx.'" cy="'.$cy.'"/><wp:docPr id="9002" name="DANUM TTE QR"/>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="'.$mediaName.'"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="'.$rid.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing>';

        $fragment = $dom->createDocumentFragment();
        $fragment->appendXML($drawingXml);
        $node->parentNode?->replaceChild($fragment, $node);

        $relsDom = new DOMDocument();
        $relsDom->preserveWhiteSpace = true;
        $relsDom->loadXML($rels, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
        $root = $relsDom->documentElement;
        foreach ($root?->childNodes ?? [] as $child) if ($child instanceof DOMElement && $child->getAttribute('Id') === $rid) { $root->removeChild($child); break; }
        $rel = $relsDom->createElementNS('http://schemas.openxmlformats.org/package/2006/relationships', 'Relationship');
        $rel->setAttribute('Id', $rid); $rel->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image'); $rel->setAttribute('Target', 'media/'.$mediaName); $root?->appendChild($rel);

        $ctDom = new DOMDocument();
        $ctDom->preserveWhiteSpace = true;
        $ctDom->loadXML($contentTypes, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
        $hasSvg = false;
        foreach ($ctDom->getElementsByTagName('Default') as $item) if (strcasecmp($item->getAttribute('Extension'), 'svg') === 0) { $hasSvg = true; break; }
        if (! $hasSvg) { $default = $ctDom->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Default'); $default->setAttribute('Extension', 'svg'); $default->setAttribute('ContentType', 'image/svg+xml'); $ctDom->documentElement?->appendChild($default); }

        $zip->addFromString('word/document.xml', $dom->saveXML() ?: $xml);
        $zip->addFromString('word/_rels/document.xml.rels', $relsDom->saveXML() ?: $rels);
        $zip->addFromString('[Content_Types].xml', $ctDom->saveXML() ?: $contentTypes);
        $zip->addFromString('word/media/'.$mediaName, $svgBytes);
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
        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) { $zip->close(); throw new RuntimeException('DOCX document.xml tidak ditemukan.'); }
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) { $zip->close(); throw new RuntimeException('DOCX document.xml tidak valid.'); }
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
        $drawings = $xpath->query('//wp:docPr[@name="DANUM TTE QR"]/parent::wp:inline/parent::w:drawing');
        if ($drawings) foreach ($drawings as $drawing) $drawing->parentNode?->removeChild($drawing);
        $zip->addFromString('word/document.xml', $dom->saveXML() ?: $xml);
        $zip->close();
    }
}
