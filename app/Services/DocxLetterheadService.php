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

    /** @return array{xml:string,rels:string,contentTypes:string,mediaName:string}|null */
    public function embed(string $xml, string $rels, string $contentTypes, Tenant $tenant): ?array
    {
        $imagePath = $this->resolvePath($tenant);
        if ($imagePath === null) return null;

        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $allowed = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif'];
        if (! isset($allowed[$extension])) throw new RuntimeException('Kop surat harus berupa PNG, JPG, JPEG, atau GIF.');

        $mediaName = 'danum-letterhead-' . substr(sha1($imagePath), 0, 12) . '.' . $extension;
        $rid = 'rIdDanumLetterhead';
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new RuntimeException('DOCX document.xml tidak valid.');

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', DocxRendererService::WORD_NS);
        $nodes = $xpath->query('//w:t[contains(., "{{letterhead}}")]');
        if (! $nodes || $nodes->length === 0) return null;

        $size = @getimagesize($imagePath);
        $width = (int) ($size[0] ?? 1200);
        $height = (int) ($size[1] ?? 300);
        $cx = 6500000;
        $cy = max(1, (int) round($height * ($cx / $width)));
        $drawingXml = '<w:r xmlns:w="' . DocxRendererService::WORD_NS . '" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:r="' . self::OFFICE_REL_NS . '"><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="' . $cx . '" cy="' . $cy . '"/><wp:docPr id="9001" name="DANUM Letterhead"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="' . $mediaName . '"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="' . $rid . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';

        foreach ($nodes as $node) {
            $parent = $node->parentNode;
            if (! $parent) continue;
            $fragment = $dom->createDocumentFragment();
            if ($fragment->appendXML($drawingXml)) $parent->replaceChild($fragment, $node);
        }

        $relsDom = new DOMDocument();
        $relsDom->preserveWhiteSpace = true;
        if (! $relsDom->loadXML($rels, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new RuntimeException('DOCX relationships tidak valid.');
        $root = $relsDom->documentElement;
        $rel = $relsDom->createElementNS(self::REL_NS, 'Relationship');
        $rel->setAttribute('Id', $rid);
        $rel->setAttribute('Type', self::OFFICE_REL_NS . '/image');
        $rel->setAttribute('Target', 'media/' . $mediaName);
        $root?->appendChild($rel);

        return [
            'xml' => $dom->saveXML() ?: $xml,
            'rels' => $relsDom->saveXML() ?: $rels,
            'contentTypes' => $contentTypes,
            'mediaName' => $mediaName,
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
}
