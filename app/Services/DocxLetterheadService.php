<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocxLetterheadService
{
    private const REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const OFFICE_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /** @return array{xml:string,rels:string,contentTypes:string,mediaName:string,mediaPath:string}|null */
    public function embed(string $xml, string $rels, string $contentTypes, Tenant $tenant): ?array
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new RuntimeException('DOCX document.xml tidak valid.');

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', DocxRendererService::WORD_NS);
        $paragraphs = $xpath->query('//w:p');
        $targets = [];
        if ($paragraphs) foreach ($paragraphs as $paragraph) if (str_contains($this->nodeText($xpath, $paragraph), '{{letterhead}}')) $targets[] = $paragraph;
        if ($targets === []) return null;

        $structured = $this->hasStructuredSettings($tenant);
        $logoPath = $structured ? $this->resolveLogoPath($tenant) : null;
        if (! $structured) return $this->embedLegacyImage($dom, $xpath, $targets, $rels, $contentTypes, $tenant);

        $mediaName = '';
        $relsDom = null;
        if ($logoPath !== null) {
            $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            if (! in_array($extension, ['png', 'jpg', 'jpeg'], true)) throw new RuntimeException('Logo kop surat harus berupa PNG, JPG, atau JPEG.');
            $mediaName = 'danum-letterhead-logo-' . substr(sha1($logoPath), 0, 12) . '.' . $extension;
            $relsDom = new DOMDocument();
            $relsDom->preserveWhiteSpace = true;
            if (! $relsDom->loadXML($rels, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new RuntimeException('DOCX relationships tidak valid.');
            $rel = $relsDom->createElementNS(self::REL_NS, 'Relationship');
            $rel->setAttribute('Id', 'rIdDanumLetterheadLogo');
            $rel->setAttribute('Type', self::OFFICE_REL_NS . '/image');
            $rel->setAttribute('Target', 'media/' . $mediaName);
            $relsDom->documentElement?->appendChild($rel);
        }

        $tableXml = $this->buildTableXml($tenant, $mediaName, $logoPath !== null);
        foreach ($targets as $paragraph) {
            $parent = $paragraph->parentNode;
            if (! $parent) continue;
            $fragment = $dom->createDocumentFragment();
            if ($fragment->appendXML($tableXml)) $parent->replaceChild($fragment, $paragraph);
        }

        return [
            'xml' => $dom->saveXML() ?: $xml,
            'rels' => $relsDom?->saveXML() ?: $rels,
            'contentTypes' => $contentTypes,
            'mediaName' => $mediaName,
            'mediaPath' => $logoPath ?? '',
        ];
    }

    public function resolvePath(Tenant $tenant): ?string
    {
        if (! $tenant->letterhead_path) return null;
        foreach (['public', 'local'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($tenant->letterhead_path)) return $disk->path($tenant->letterhead_path);
        }
        return null;
    }

    public function resolveLogoPath(Tenant $tenant): ?string
    {
        if (! $tenant->logo || str_starts_with($tenant->logo, 'http://') || str_starts_with($tenant->logo, 'https://') || str_starts_with($tenant->logo, '/')) return null;
        foreach (['public', 'local'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($tenant->logo)) return $disk->path($tenant->logo);
        }
        return null;
    }

    private function hasStructuredSettings(Tenant $tenant): bool
    {
        return collect([$tenant->letterhead_line1, $tenant->letterhead_line2, $tenant->letterhead_line3, $tenant->postal_code, $tenant->address, $tenant->website, $tenant->email])
            ->contains(fn ($value): bool => trim((string) $value) !== '');
    }

    /** @param list<\DOMNode> $targets */
    private function embedLegacyImage(DOMDocument $dom, DOMXPath $xpath, array $targets, string $rels, string $contentTypes, Tenant $tenant): ?array
    {
        $imagePath = $this->resolvePath($tenant);
        if ($imagePath === null) return null;
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        if (! in_array($extension, ['png', 'jpg', 'jpeg', 'gif'], true)) throw new RuntimeException('Kop surat harus berupa PNG, JPG, JPEG, atau GIF.');
        $mediaName = 'danum-letterhead-' . substr(sha1($imagePath), 0, 12) . '.' . $extension;
        $rid = 'rIdDanumLetterhead';
        $size = @getimagesize($imagePath);
        $width = (int) ($size[0] ?? 1200);
        $height = (int) ($size[1] ?? 300);
        $cx = 6500000;
        $cy = max(1, (int) round($height * ($cx / $width)));
        $drawingXml = '<w:r xmlns:w="' . DocxRendererService::WORD_NS . '" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:r="' . self::OFFICE_REL_NS . '"><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="' . $cx . '" cy="' . $cy . '"/><wp:docPr id="9001" name="DANUM Letterhead"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="' . $mediaName . '"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="' . $rid . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
        foreach ($targets as $paragraph) {
            $nodes = $xpath->query('.//w:t', $paragraph);
            if (! $nodes) continue;
            foreach ($nodes as $node) if (str_contains($node->textContent, '{{letterhead}}')) {
                $parent = $node->parentNode; if (! $parent) continue;
                $fragment = $dom->createDocumentFragment(); if ($fragment->appendXML($drawingXml)) $parent->replaceChild($fragment, $node);
            }
        }
        $relsDom = new DOMDocument(); $relsDom->preserveWhiteSpace = true;
        if (! $relsDom->loadXML($rels, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new RuntimeException('DOCX relationships tidak valid.');
        $rel = $relsDom->createElementNS(self::REL_NS, 'Relationship'); $rel->setAttribute('Id', $rid); $rel->setAttribute('Type', self::OFFICE_REL_NS . '/image'); $rel->setAttribute('Target', 'media/' . $mediaName); $relsDom->documentElement?->appendChild($rel);
        return ['xml' => $dom->saveXML() ?: '', 'rels' => $relsDom->saveXML() ?: $rels, 'contentTypes' => $contentTypes, 'mediaName' => $mediaName, 'mediaPath' => $imagePath];
    }

    private function buildTableXml(Tenant $tenant, string $mediaName, bool $hasLogo): string
    {
        $rows = [];
        foreach ([
            [$tenant->letterhead_line1, (int) ($tenant->letterhead_line1_size ?? 15)],
            [$tenant->letterhead_line2, (int) ($tenant->letterhead_line2_size ?? 13)],
            [$tenant->letterhead_line3, (int) ($tenant->letterhead_line3_size ?? 11)],
        ] as [$value, $size]) {
            if (trim((string) $value) !== '') $rows[] = $this->textParagraph((string) $value, $size, true);
        }

        $meta = array_values(array_filter([
            trim((string) $tenant->address) !== '' ? trim((string) $tenant->address) : null,
            trim((string) $tenant->postal_code) !== '' ? 'Kode Pos ' . trim((string) $tenant->postal_code) : null,
            trim((string) $tenant->phone) !== '' ? 'Telp. ' . trim((string) $tenant->phone) : null,
            trim((string) $tenant->email) !== '' ? trim((string) $tenant->email) : null,
            trim((string) $tenant->website) !== '' ? trim((string) $tenant->website) : null,
        ]));
        if ($meta !== []) $rows[] = $this->textParagraph(implode(', ', $meta), (int) ($tenant->letterhead_meta_size ?? 8), false);
        if ($rows === []) $rows[] = $this->textParagraph($tenant->name, (int) ($tenant->letterhead_line1_size ?? 15), true);

        $textWidth = $hasLogo ? '7200' : '9000';
        $grid = $hasLogo ? '<w:gridCol w:w="1800"/><w:gridCol w:w="7200"/>' : '<w:gridCol w:w="9000"/>';
        $textCell = '<w:tc><w:tcPr><w:tcW w:w="' . $textWidth . '" w:type="dxa"/><w:vAlign w:val="center"/><w:tcMar><w:left w:w="120" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tcMar></w:tcPr>' . implode('', $rows) . '</w:tc>';
        $logoCell = '';
        if ($hasLogo) $logoCell = '<w:tc><w:tcPr><w:tcW w:w="1800" w:type="dxa"/><w:vAlign w:val="center"/><w:tcMar><w:left w:w="60" w:type="dxa"/><w:right w:w="120" w:type="dxa"/></w:tcMar></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:drawing><wp:inline xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:r="' . self::OFFICE_REL_NS . '" distT="0" distB="0" distL="0" distR="0"><wp:extent cx="1100000" cy="1100000"/><wp:docPr id="9002" name="DANUM Letterhead Logo"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="' . htmlspecialchars($mediaName, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rIdDanumLetterheadLogo"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="1100000" cy="1100000"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p></w:tc>';

        return '<w:tbl xmlns:w="' . DocxRendererService::WORD_NS . '"><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:jc w:val="left"/><w:tblCellMar><w:top w:w="0" w:type="dxa"/><w:left w:w="0" w:type="dxa"/><w:bottom w:w="0" w:type="dxa"/><w:right w:w="0" w:type="dxa"/></w:tblCellMar><w:tblBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="single" w:sz="16" w:space="1"/><w:right w:val="nil"/><w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders></w:tblPr><w:tblGrid>' . $grid . '</w:tblGrid><w:tr>' . $logoCell . $textCell . '</w:tr></w:tbl>';
    }

    private function textParagraph(string $text, int $points, bool $bold): string
    {
        $halfPoints = $points * 2;
        $escaped = htmlspecialchars(trim($text), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="' . $halfPoints . '"/><w:szCs w:val="' . $halfPoints . '"/>' . ($bold ? '<w:b/><w:bCs/>' : '') . '</w:rPr><w:t xml:space="preserve">' . $escaped . '</w:t></w:r></w:p>';
    }

    private function nodeText(DOMXPath $xpath, \DOMNode $node): string
    {
        $nodes = $xpath->query('.//w:t', $node); $text = '';
        if ($nodes) foreach ($nodes as $textNode) $text .= $textNode->textContent;
        return $text;
    }
}
