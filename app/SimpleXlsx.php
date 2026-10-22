<?php
/**
 * SimpleXlsx — genera archivos Excel (.xlsx) sin dependencias usando ZipArchive.
 * Varias hojas; primera fila en negrita con fondo; números como números.
 */
class SimpleXlsx
{
    private array $sheets = [];

    /** $rows: array de filas; la primera fila se trata como encabezado. $widths opcional. */
    public function addSheet(string $name, array $rows, array $widths = [], ?string $title = null): void
    {
        $name = mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name), 0, 31);
        $this->sheets[] = ['name' => $name, 'rows' => $rows, 'widths' => $widths, 'title' => $title];
    }

    private static function col(int $i): string
    {
        $s = '';
        $i++;
        while ($i > 0) { $m = ($i - 1) % 26; $s = chr(65 + $m) . $s; $i = intdiv($i - 1, 26); }
        return $s;
    }

    private static function x(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public function save(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el Excel');
        }
        $n = count($this->sheets);
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $n; $i++) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ct .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->sheets as $i => $s) {
            $id = $i + 1;
            $wb .= '<sheet name="' . self::x($s['name']) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
            $rels .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $id . '.xml"/>';
        }
        $wb .= '</sheets></workbook>';
        $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
        // estilos: 0 normal, 1 encabezado, 2 número con miles, 3 título
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0"/></numFmts><fonts count="3"><font><sz val="10"/><name val="Arial"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Arial"/></font><font><b/><sz val="13"/><name val="Arial"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF820503"/></patternFill></fill></fills><borders count="2"><border/><border><left style="thin"><color rgb="FFBFC7D5"/></left><right style="thin"><color rgb="FFBFC7D5"/></right><top style="thin"><color rgb="FFBFC7D5"/></top><bottom style="thin"><color rgb="FFBFC7D5"/></bottom></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf xfId="0" borderId="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf><xf xfId="0" fontId="1" fillId="2" borderId="1" applyFont="1" applyFill="1" applyBorder="1"><alignment vertical="center" wrapText="1"/></xf><xf xfId="0" numFmtId="164" borderId="1" applyNumberFormat="1" applyBorder="1"/><xf xfId="0" fontId="2" applyFont="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
        foreach ($this->sheets as $i => $s) {
            $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
            $offset = $s['title'] ? 2 : 0;
            $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . ($offset + 1) . '" topLeftCell="A' . ($offset + 2) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
            if ($s['widths']) {
                $xml .= '<cols>';
                foreach ($s['widths'] as $c => $w) {
                    $xml .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . (float)$w . '" customWidth="1"/>';
                }
                $xml .= '</cols>';
            }
            $xml .= '<sheetData>';
            if ($s['title']) {
                $xml .= '<row r="1"><c r="A1" t="inlineStr" s="3"><is><t>' . self::x($s['title']) . '</t></is></c></row>';
            }
            foreach ($s['rows'] as $r => $row) {
                $rn = $r + 1 + $offset;
                $xml .= '<row r="' . $rn . '">';
                foreach (array_values($row) as $c => $v) {
                    $ref = self::col($c) . $rn;
                    $style = $r === 0 ? 1 : 0;
                    if ($v === null || $v === '') {
                        $xml .= '<c r="' . $ref . '" s="' . $style . '"/>';
                    } elseif ((is_int($v) || is_float($v)) && $r > 0) {
                        $xml .= '<c r="' . $ref . '" s="2"><v>' . $v . '</v></c>';
                    } else {
                        $xml .= '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t xml:space="preserve">' . self::x((string)$v) . '</t></is></c>';
                    }
                }
                $xml .= '</row>';
            }
            $xml .= '</sheetData>';
            if (count($s['rows']) > 0) {
                $last = self::col(max(0, count($s['rows'][0]) - 1)) . (count($s['rows']) + $offset);
                $xml .= '<autoFilter ref="A' . (1 + $offset) . ':' . $last . '"/>';
            }
            $xml .= '</worksheet>';
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
        }
        $zip->close();
    }
}
