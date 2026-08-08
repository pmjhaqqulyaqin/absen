<?php
/**
 * SimpleXLSX Writer - Pure PHP, tanpa library tambahan
 * Menggunakan ZipArchive (built-in PHP) untuk membuat file .xlsx
 */
class SimpleXLSX {
    private $rows    = [];
    private $styles  = [];
    private $colWidths = [];
    private $merges  = [];

    // Style index constants
    const S_NORMAL       = 0;
    const S_BOLD         = 1;
    const S_HEADER       = 2;  // bold + bg biru + white text + border
    const S_TITLE        = 3;  // bold + center + bigger
    const S_SUBTITLE     = 4;  // bold + center
    const S_KOP_NAMA     = 5;  // bold + center + font besar
    const S_CENTER       = 6;
    const S_NUMBER       = 7;
    const S_PERCENT      = 8;
    const S_HADIR        = 9;  // bg hijau muda
    const S_TERLAMBAT    = 10; // bg kuning muda
    const S_ALPA         = 11; // bg merah muda
    const S_BORDER       = 12; // normal + border

    /** Tambah baris. $cells = array of [value, style_index] atau value saja */
    public function addRow(array $cells, $defaultStyle = self::S_BORDER) {
        $processed = [];
        foreach ($cells as $cell) {
            if (is_array($cell)) {
                $processed[] = ['v' => $cell[0], 's' => $cell[1] ?? $defaultStyle];
            } else {
                $processed[] = ['v' => $cell, 's' => $defaultStyle];
            }
        }
        $this->rows[] = $processed;
        return count($this->rows) - 1; // return row index
    }

    /** Tambah baris kosong */
    public function addEmptyRow() {
        $this->rows[] = [];
        return count($this->rows) - 1;
    }

    /** Set lebar kolom (1-based) */
    public function setColWidth($col, $width) {
        $this->colWidths[$col] = $width;
    }

    /** Merge cells: contoh "A1:D1" */
    public function mergeCells($range) {
        $this->merges[] = $range;
    }

    /** Generate dan output file xlsx */
    public function download($filename) {
        $xlsx = $this->build();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($xlsx));
        header('Cache-Control: max-age=0');
        echo $xlsx;
        exit;
    }

    /** Build xlsx content */
    private function build() {
        $sheetXml   = $this->buildSheet();
        $stylesXml  = $this->buildStyles();
        $sharedXml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="0" uniqueCount="0"></sst>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="Rekap" sheetId="1" r:id="rId1"/></sheets>
</workbook>';

        // Create in-memory zip
        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml',         $contentTypes);
        $zip->addFromString('_rels/.rels',                 $rels);
        $zip->addFromString('xl/workbook.xml',             $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels',  $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml',    $sheetXml);
        $zip->addFromString('xl/styles.xml',               $stylesXml);
        $zip->addFromString('xl/sharedStrings.xml',        $sharedXml);
        $zip->close();

        $content = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $content;
    }

    /** Build worksheet XML */
    private function buildSheet() {
        $cols = '';
        foreach ($this->colWidths as $col => $w) {
            $cols .= '<col min="'.$col.'" max="'.$col.'" width="'.$w.'" customWidth="1"/>';
        }
        if ($cols) $cols = '<cols>'.$cols.'</cols>';

        $mergeXml = '';
        if ($this->merges) {
            $mergeXml = '<mergeCells count="'.count($this->merges).'">';
            foreach ($this->merges as $m) $mergeXml .= '<mergeCell ref="'.$m.'"/>';
            $mergeXml .= '</mergeCells>';
        }

        $sheetData = '<sheetData>';
        foreach ($this->rows as $ri => $row) {
            $rowNum = $ri + 1;
            if (empty($row)) {
                $sheetData .= '<row r="'.$rowNum.'"><c r="A'.$rowNum.'" s="0"><v></v></c></row>';
                continue;
            }
            $sheetData .= '<row r="'.$rowNum.'">';
            foreach ($row as $ci => $cell) {
                $col  = $this->colLetter($ci);
                $ref  = $col . $rowNum;
                $val  = $cell['v'];
                $si   = $cell['s'];

                if ($val === null || $val === '') {
                    $sheetData .= '<c r="'.$ref.'" s="'.$si.'"/>';
                } elseif (is_numeric($val) && !is_string($val)) {
                    $sheetData .= '<c r="'.$ref.'" s="'.$si.'" t="n"><v>'.htmlspecialchars((string)$val, ENT_XML1).'</v></c>';
                } else {
                    $safe = htmlspecialchars((string)$val, ENT_XML1);
                    $sheetData .= '<c r="'.$ref.'" s="'.$si.'" t="inlineStr"><is><t>'.$safe.'</t></is></c>';
                }
            }
            $sheetData .= '</row>';
        }
        $sheetData .= '</sheetData>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetView tabSelected="1" workbookViewId="0"><selection activeCell="A1" sqref="A1"/></sheetView>
  '.$cols.'
  '.$sheetData.'
  '.$mergeXml.'
</worksheet>';
    }

    /** Build styles XML */
    private function buildStyles() {
        // Fonts
        $fonts = [
            /*0*/ '<font><sz val="11"/><name val="Calibri"/></font>',                                                          // normal
            /*1*/ '<font><b/><sz val="11"/><name val="Calibri"/></font>',                                                      // bold
            /*2*/ '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>',                               // bold white (header)
            /*3*/ '<font><b/><sz val="14"/><name val="Calibri"/></font>',                                                      // title
            /*4*/ '<font><b/><sz val="12"/><name val="Calibri"/></font>',                                                      // subtitle
            /*5*/ '<font><b/><sz val="16"/><name val="Calibri"/></font>',                                                      // kop nama sekolah
        ];

        // Fills
        $fills = [
            /*0*/ '<fill><patternFill patternType="none"/></fill>',
            /*1*/ '<fill><patternFill patternType="gray125"/></fill>',
            /*2*/ '<fill><patternFill patternType="solid"><fgColor rgb="FF1E40AF"/></patternFill></fill>',  // biru tua (header)
            /*3*/ '<fill><patternFill patternType="solid"><fgColor rgb="FFdcfce7"/></patternFill></fill>',  // hijau muda (hadir)
            /*4*/ '<fill><patternFill patternType="solid"><fgColor rgb="FFfef9c3"/></patternFill></fill>',  // kuning muda (terlambat)
            /*5*/ '<fill><patternFill patternType="solid"><fgColor rgb="FFffe4e6"/></patternFill></fill>',  // merah muda (alpa)
            /*6*/ '<fill><patternFill patternType="solid"><fgColor rgb="FFe0f2fe"/></patternFill></fill>',  // biru muda (kop bg)
        ];

        $border_thin = '<border><left style="thin"><color rgb="FFBFBFBF"/></left><right style="thin"><color rgb="FFBFBFBF"/></right><top style="thin"><color rgb="FFBFBFBF"/></top><bottom style="thin"><color rgb="FFBFBFBF"/></bottom></border>';
        $border_none = '<border><left/><right/><top/><bottom/></border>';
        $border_bottom = '<border><left/><right/><top/><bottom style="medium"><color rgb="FF1E40AF"/></bottom></border>';

        $borders = [
            /*0*/ $border_none,
            /*1*/ $border_thin,
            /*2*/ $border_bottom,
        ];

        // Number formats
        $numFmts = '<numFmt numFmtId="164" formatCode="0.0&quot;%&quot;"/>';

        // Cell XFs: fontId, fillId, borderId, applyFont, applyFill, applyBorder, applyAlignment + alignment
        // Format: [fontId, fillId, borderId, horizontal, vertical, wrapText, numFmtId]
        $xfs = [
            /*0  S_NORMAL    */ [0, 0, 0, 'left',   '', 0, 0],
            /*1  S_BOLD      */ [1, 0, 0, 'left',   '', 0, 0],
            /*2  S_HEADER    */ [2, 2, 1, 'center', '', 0, 0],
            /*3  S_TITLE     */ [3, 0, 0, 'center', '', 0, 0],
            /*4  S_SUBTITLE  */ [4, 0, 0, 'center', '', 0, 0],
            /*5  S_KOP_NAMA  */ [5, 6, 2, 'center', 'center', 0, 0],
            /*6  S_CENTER    */ [0, 0, 1, 'center', '', 0, 0],
            /*7  S_NUMBER    */ [0, 0, 1, 'center', '', 0, 0],
            /*8  S_PERCENT   */ [0, 0, 1, 'center', '', 0, 164],
            /*9  S_HADIR     */ [1, 3, 1, 'center', '', 0, 0],
            /*10 S_TERLAMBAT */ [1, 4, 1, 'center', '', 0, 0],
            /*11 S_ALPA      */ [1, 5, 1, 'center', '', 0, 0],
            /*12 S_BORDER    */ [0, 0, 1, 'left',   '', 1, 0],
        ];

        $fontsXml  = '<fonts count="'.count($fonts).'">'.implode('',$fonts).'</fonts>';
        $fillsXml  = '<fills count="'.count($fills).'">'.implode('',$fills).'</fills>';
        $bordersXml= '<borders count="'.count($borders).'">'.implode('',$borders).'</borders>';

        $xfXml = '<cellXfs>';
        foreach ($xfs as $xf) {
            [$fid, $fill, $bord, $halign, $valign, $wrap, $nfmt] = $xf;
            $align = '';
            if ($halign || $valign || $wrap) {
                $align = '<alignment';
                if ($halign) $align .= ' horizontal="'.$halign.'"';
                if ($valign) $align .= ' vertical="'.$valign.'"';
                if ($wrap)   $align .= ' wrapText="1"';
                $align .= '/>';
            }
            $xfXml .= '<xf numFmtId="'.$nfmt.'" fontId="'.$fid.'" fillId="'.$fill.'" borderId="'.$bord.'"';
            if ($fid)   $xfXml .= ' applyFont="1"';
            if ($fill)  $xfXml .= ' applyFill="1"';
            if ($bord)  $xfXml .= ' applyBorder="1"';
            if ($nfmt)  $xfXml .= ' applyNumberFormat="1"';
            if ($align) $xfXml .= ' applyAlignment="1">';
            else        $xfXml .= '>';
            $xfXml .= $align . '</xf>';
        }
        $xfXml .= '</cellXfs>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <numFmts count="1">'.$numFmts.'</numFmts>
  '.$fontsXml.'
  '.$fillsXml.'
  '.$bordersXml.'
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  '.$xfXml.'
</styleSheet>';
    }

    private function colLetter($idx) {
        $letters = '';
        $idx++;
        while ($idx > 0) {
            $idx--;
            $letters = chr(65 + ($idx % 26)) . $letters;
            $idx = intdiv($idx, 26);
        }
        return $letters;
    }
}
