<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use setasign\Fpdi\Fpdi;

/**
 * FPDI writer that stores the generated PDF on disk instead of keeping the
 * complete document buffer in PHP memory.
 *
 * Adapted from the file-buffering technique published by BARNZ/fpdi-file.
 */
final class FileBufferedFpdi extends Fpdi
{
    /** @var resource|null */
    protected $fileHandle = null;

    public function openFile(string $path): void
    {
        if (is_resource($this->fileHandle)) {
            fclose($this->fileHandle);
        }

        $handle = fopen($path, 'wb');

        if ($handle === false) {
            $this->Error('Unable to create output file: ' . $path);
        }

        $this->fileHandle = $handle;
        $this->_putheader();
    }

    public function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '')
    {
        if (! isset($this->images[$file])) {
            $info = getimagesize($file);

            if ($info === false) {
                $this->Error('Missing or incorrect image file: ' . $file);
            }

            $this->images[$file] = [
                'w' => $info[0],
                'h' => $info[1],
                'type' => $info[2],
                'i' => count($this->images) + 1,
            ];
        }

        parent::Image($file, $x, $y, $w, $h, $type, $link);
    }

    public function Output($dest = '', $name = '', $isUTF8 = false)
    {
        if ($this->state < 3) {
            $this->Close();
        }
    }

    public function _endpage()
    {
        parent::_endpage();

        $this->_putstreamobject($this->pages[$this->page]);
        unset($this->pages[$this->page]);
    }

    public function _getoffset()
    {
        return ftell($this->fileHandle);
    }

    protected function _put($s, $newLine = true)
    {
        if (! is_resource($this->fileHandle)) {
            $this->Error('PDF output file is not open.');
        }

        $s = (string) $s;

        if ($newLine) {
            fwrite($this->fileHandle, $s . "\n", strlen($s) + 1);
            return;
        }

        fwrite($this->fileHandle, $s, strlen($s));
    }

    public function _putpages()
    {
        $pageCount = $this->page;

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $this->PageInfo[$pageNumber]['n'] = $this->n + $pageNumber;
        }

        if ($this->DefOrientation === 'P') {
            $widthPt = $this->DefPageSize[0] * $this->k;
            $heightPt = $this->DefPageSize[1] * $this->k;
        } else {
            $widthPt = $this->DefPageSize[1] * $this->k;
            $heightPt = $this->DefPageSize[0] * $this->k;
        }

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $this->_newobj();
            $this->_put('<</Type /Page');
            $this->_put('/Parent 1 0 R');

            if (isset($this->PageInfo[$pageNumber]['size'])) {
                $this->_put(sprintf(
                    '/MediaBox [0 0 %.2F %.2F]',
                    $this->PageInfo[$pageNumber]['size'][0],
                    $this->PageInfo[$pageNumber]['size'][1],
                ));
            }

            if (isset($this->PageInfo[$pageNumber]['rotation'])) {
                $this->_put('/Rotate ' . $this->PageInfo[$pageNumber]['rotation']);
            }

            $this->_put('/Resources 2 0 R');

            if (isset($this->PageLinks[$pageNumber])) {
                $annotations = '/Annots [';

                foreach ($this->PageLinks[$pageNumber] as $pageLink) {
                    $rect = sprintf(
                        '%.2F %.2F %.2F %.2F',
                        $pageLink[0],
                        $pageLink[1],
                        $pageLink[0] + $pageLink[2],
                        $pageLink[1] - $pageLink[3],
                    );

                    $annotations .= '<</Type /Annot /Subtype /Link /Rect [' . $rect . '] /Border [0 0 0] ';

                    if (is_string($pageLink[4])) {
                        $annotations .= '/A <</S /URI /URI ' . $this->_textstring($pageLink[4]) . '>>>>';
                    } else {
                        $link = $this->links[$pageLink[4]];
                        $height = isset($this->PageInfo[$link[0]]['size'])
                            ? $this->PageInfo[$link[0]]['size'][1]
                            : $heightPt;

                        $annotations .= sprintf(
                            '/Dest [%d 0 R /XYZ 0 %.2F null]>>',
                            $this->PageInfo[$link[0]]['n'],
                            $height - $link[1] * $this->k,
                        );
                    }
                }

                $this->_put($annotations . ']');
            }

            if ($this->WithAlpha) {
                $this->_put('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
            }

            $this->_put('/Contents ' . (2 + $pageNumber) . ' 0 R>>');
            $this->_put('endobj');
        }

        $this->offsets[1] = $this->_getoffset();
        $this->_put('1 0 obj');
        $this->_put('<</Type /Pages');

        $kids = '/Kids [';
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $kids .= (2 + $pageCount + $pageNumber) . ' 0 R ';
        }

        $this->_put($kids . ']');
        $this->_put('/Count ' . $pageCount);
        $this->_put(sprintf('/MediaBox [0 0 %.2F %.2F]', $widthPt, $heightPt));
        $this->_put('>>');
        $this->_put('endobj');
    }

    public function _putheader()
    {
        if ($this->_getoffset() === 0) {
            parent::_putheader();
        }
    }

    public function _enddoc()
    {
        parent::_enddoc();

        if (is_resource($this->fileHandle)) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }
}
