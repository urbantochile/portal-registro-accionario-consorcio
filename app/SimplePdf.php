<?php
/**
 * SimplePdf — generador PDF mínimo sin dependencias (PHP 8).
 * Fuentes: Helvetica / Helvetica-Bold (WinAnsi). Unidades: milímetros.
 * Soporta: páginas A4 (P/L), texto, celdas con ajuste de línea, rectángulos,
 * líneas, colores, imágenes JPEG y PNG (con transparencia vía GD) y
 * total de páginas mediante el alias {nb}.
 */
class SimplePdf
{
    private array $pages = [];
    private int $page = 0;
    private float $k;
    private float $w;
    private float $h;
    private string $orientation;
    private string $font = 'Helvetica';
    private float $size = 10;
    private string $fill = '0 0 0 rg';
    private string $stroke = '0 0 0 RG';
    private string $textColor = '0 0 0 rg';
    private array $images = [];
    private static ?array $metrics = null;
    public array $meta = ['Title' => '', 'Author' => '', 'Subject' => ''];
    /** @var callable|null */
    public $footer = null;
    /** @var callable|null */
    public $header = null;
    public ?float $__fs = null;
    public float $x = 15;
    public float $y = 15;
    public float $lMargin = 15;
    public float $rMargin = 15;
    public float $tMargin = 15;
    public float $bMargin = 18;

    public function __construct(string $orientation = 'P')
    {
        $this->k = 72 / 25.4;
        $this->orientation = strtoupper($orientation);
        [$this->w, $this->h] = $this->orientation === 'L' ? [297, 210] : [210, 297];
        if (self::$metrics === null) {
            self::$metrics = require __DIR__ . '/font_metrics.php';
        }
    }

    public function pageWidth(): float { return $this->w; }
    public function pageHeight(): float { return $this->h; }
    public function pageNo(): int { return $this->page; }

    public function addPage(): void
    {
        if ($this->page > 0 && $this->footer) {
            ($this->footer)($this);
        }
        $this->page++;
        $this->pages[$this->page] = '';
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->out('0.2 w');
        if ($this->header) {
            ($this->header)($this);
        }
    }

    public function checkPageBreak(float $h): bool
    {
        if ($this->y + $h > $this->h - $this->bMargin) {
            $this->addPage();
            return true;
        }
        return false;
    }

    private function out(string $s): void
    {
        $this->pages[$this->page] .= $s . "\n";
    }

    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [130, 5, 3];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private function rgb(array|string $c): string
    {
        if (is_string($c)) {
            $c = self::hexToRgb($c);
        }
        return sprintf('%.3F %.3F %.3F', $c[0] / 255, $c[1] / 255, $c[2] / 255);
    }

    public function setFillColor(array|string $c): void { $this->fill = $this->rgb($c) . ' rg'; $this->out($this->fill); }
    public function setDrawColor(array|string $c): void { $this->stroke = $this->rgb($c) . ' RG'; $this->out($this->stroke); }
    public function setTextColor(array|string $c): void { $this->textColor = $this->rgb($c) . ' rg'; }
    public function setLineWidth(float $mm): void { $this->out(sprintf('%.2F w', $mm * $this->k)); }

    /** Fuentes estándar: familia 'helvetica' (estilos '', B) o 'times' (estilos '', B, I, BI). */
    private const FONT_KEYS = ['Helvetica' => 'F1', 'Helvetica-Bold' => 'F2', 'Times-Roman' => 'F3', 'Times-Bold' => 'F4', 'Times-Italic' => 'F5', 'Times-BoldItalic' => 'F6'];
    public float $charSpacing = 0;

    public function setFont(string $style = '', float $size = 10, string $family = 'helvetica'): void
    {
        $st = strtoupper($style);
        $b = str_contains($st, 'B'); $i = str_contains($st, 'I');
        if (strtolower($family) === 'times') {
            $this->font = $b && $i ? 'Times-BoldItalic' : ($b ? 'Times-Bold' : ($i ? 'Times-Italic' : 'Times-Roman'));
        } else {
            $this->font = $b ? 'Helvetica-Bold' : 'Helvetica';
        }
        $this->size = $size;
    }

    /** Polilínea (puntos en mm). $close cierra; $style 'D' trazo, 'F' relleno, 'FD' ambos. */
    public function polyline(array $pts, bool $close = false, string $style = 'D'): void
    {
        if (count($pts) < 2) return;
        $o = '';
        foreach ($pts as $i => [$x, $y]) {
            $o .= sprintf('%.2F %.2F %s ', $x * $this->k, ($this->h - $y) * $this->k, $i === 0 ? 'm' : 'l');
        }
        if ($close) $o .= 'h ';
        $o .= match (strtoupper($style)) { 'F' => 'f', 'FD', 'DF' => 'B', default => 'S' };
        $this->out($o);
    }

    public function circle(float $cx, float $cy, float $r, string $style = 'D', int $seg = 72): void
    {
        $pts = [];
        for ($i = 0; $i < $seg; $i++) { $a = 2 * M_PI * $i / $seg; $pts[] = [$cx + $r * cos($a), $cy + $r * sin($a)]; }
        $this->polyline($pts, true, $style);
    }

    /** Texto centrado horizontalmente en $cx. */
    public function textCenter(float $cx, float $y, string $txt): void
    {
        $this->text($cx - $this->textWidth($txt) / 2, $y, $txt);
    }

    public static function enc(string $s): string
    {
        $r = @iconv('UTF-8', 'CP1252//TRANSLIT', $s);
        return $r === false ? mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8') : $r;
    }

    public function textWidth(string $s): float
    {
        $s = self::enc($s);
        $m = self::$metrics[$this->font];
        $w = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $w += $m[ord($s[$i])];
        }
        return $w * $this->size / 1000 / $this->k + ($len > 1 ? ($len - 1) * $this->charSpacing / $this->k : 0);
    }

    private function escape(string $s): string
    {
        return str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], $s);
    }

    /** Texto en posición absoluta (x,y = línea base aproximada al tope + tamaño). */
    public function text(float $x, float $y, string $txt): void
    {
        $fontKey = self::FONT_KEYS[$this->font];
        $s = $this->escape(self::enc($txt));
        $this->out(sprintf('BT %s %.2F Tc /%s %.2F Tf %.2F %.2F Td (%s) Tj ET', $this->textColor, $this->charSpacing, $fontKey, $this->size,
            $x * $this->k, ($this->h - $y) * $this->k, $s));
        $this->out($this->fill);
    }

    public function rect(float $x, float $y, float $w, float $h, string $style = 'D'): void
    {
        $op = match (strtoupper($style)) { 'F' => 'f', 'FD', 'DF' => 'B', default => 'S' };
        $this->out(sprintf('%.2F %.2F %.2F %.2F re %s', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k, $op));
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1 * $this->k, ($this->h - $y1) * $this->k, $x2 * $this->k, ($this->h - $y2) * $this->k));
    }

    /** Parte un texto en líneas que caben en $w mm. */
    public function wrap(string $txt, float $w): array
    {
        $lines = [];
        foreach (preg_split("/\r\n|\n|\r/", $txt) as $para) {
            $words = preg_split('/\s+/', trim($para));
            $cur = '';
            foreach ($words as $word) {
                if ($word === '') continue;
                $try = $cur === '' ? $word : $cur . ' ' . $word;
                if ($this->textWidth($try) <= $w) {
                    $cur = $try;
                } else {
                    if ($cur !== '') $lines[] = $cur;
                    // palabra más larga que el ancho: cortar
                    while ($this->textWidth($word) > $w && mb_strlen($word) > 1) {
                        $n = mb_strlen($word);
                        while ($n > 1 && $this->textWidth(mb_substr($word, 0, $n)) > $w) $n--;
                        $lines[] = mb_substr($word, 0, $n);
                        $word = mb_substr($word, $n);
                    }
                    $cur = $word;
                }
            }
            $lines[] = $cur;
        }
        return $lines;
    }

    /** Celda de una línea. align: L, C, R. border: '' o 'D'. fill: bool. */
    public function cell(float $w, float $h, string $txt = '', string $border = '', string $align = 'L', bool $fill = false, bool $ln = false): void
    {
        if ($fill || $border !== '') {
            $this->rect($this->x, $this->y, $w, $h, $fill && $border !== '' ? 'FD' : ($fill ? 'F' : 'D'));
        }
        if ($txt !== '') {
            // recortar si no cabe
            while ($this->textWidth($txt) > $w - 2 && mb_strlen($txt) > 1) {
                $txt = mb_substr($txt, 0, -2) . '…';
            }
            $tw = $this->textWidth($txt);
            $tx = match ($align) { 'C' => $this->x + ($w - $tw) / 2, 'R' => $this->x + $w - 1.2 - $tw, default => $this->x + 1.2 };
            $ty = $this->y + $h / 2 + $this->size * 0.35 / $this->k;
            $this->text($tx, $ty, $txt);
        }
        if ($ln) {
            $this->x = $this->lMargin;
            $this->y += $h;
        } else {
            $this->x += $w;
        }
    }

    /** Párrafo con ajuste de línea. Devuelve altura usada. */
    public function multiCell(float $w, float $lh, string $txt, string $align = 'L'): float
    {
        $lines = $this->wrap($txt, $w - 2.4);
        $startY = $this->y;
        foreach ($lines as $l) {
            $this->checkPageBreak($lh);
            $this->cell($w, $lh, $l, '', $align, false, false);
            $this->x = $this->lMargin;
            $this->y += $lh;
        }
        return $this->y - $startY;
    }

    public function image(string $file, float $x, float $y, float $w = 0, float $h = 0): void
    {
        if (!is_file($file)) return;
        $key = md5_file($file);
        if (!isset($this->images[$key])) {
            $this->images[$key] = $this->loadImage($file);
            $this->images[$key]['n'] = count($this->images);
        }
        $info = $this->images[$key];
        if ($w == 0 && $h == 0) { $w = $info['w'] / 4; }
        if ($w == 0) $w = $h * $info['w'] / $info['h'];
        if ($h == 0) $h = $w * $info['h'] / $info['w'];
        $this->out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q', $w * $this->k, $h * $this->k, $x * $this->k, ($this->h - $y - $h) * $this->k, $info['n']));
    }

    /** Ajusta una imagen dentro de una caja manteniendo proporción. */
    public function imageFit(string $file, float $x, float $y, float $bw, float $bh): void
    {
        $sz = @getimagesize($file);
        if (!$sz) return;
        $r = min($bw / $sz[0], $bh / $sz[1]);
        $w = $sz[0] * $r; $h = $sz[1] * $r;
        $this->image($file, $x + ($bw - $w) / 2, $y + ($bh - $h) / 2, $w, $h);
    }

    private function loadImage(string $file): array
    {
        $sz = getimagesize($file);
        if ($sz[2] === IMAGETYPE_JPEG) {
            $cs = ($sz['channels'] ?? 3) == 4 ? 'DeviceCMYK' : (($sz['channels'] ?? 3) == 1 ? 'DeviceGray' : 'DeviceRGB');
            return ['w' => $sz[0], 'h' => $sz[1], 'cs' => $cs, 'filter' => 'DCTDecode', 'data' => file_get_contents($file), 'smask' => null];
        }
        // PNG u otros: via GD, RGB + canal alfa
        $im = @imagecreatefromstring(file_get_contents($file));
        if (!$im) throw new RuntimeException('Imagen no soportada');
        $w = imagesx($im); $h = imagesy($im);
        // limitar resolución para PDFs livianos
        if ($w > 1200) {
            $nh = (int)round($h * 1200 / $w);
            $tmp = imagecreatetruecolor(1200, $nh);
            imagealphablending($tmp, false); imagesavealpha($tmp, true);
            imagecopyresampled($tmp, $im, 0, 0, 0, 0, 1200, $nh, $w, $h);
            $im = $tmp; $w = 1200; $h = $nh;
        }
        if (!imageistruecolor($im)) imagepalettetotruecolor($im);
        $rgb = ''; $alpha = ''; $hasAlpha = false;
        for ($j = 0; $j < $h; $j++) {
            for ($i = 0; $i < $w; $i++) {
                $c = imagecolorat($im, $i, $j);
                $a = ($c >> 24) & 0x7F;
                $rgb .= chr(($c >> 16) & 0xFF) . chr(($c >> 8) & 0xFF) . chr($c & 0xFF);
                $av = 255 - (int)round($a * 255 / 127);
                if ($av < 255) $hasAlpha = true;
                $alpha .= chr($av);
            }
        }
        return ['w' => $w, 'h' => $h, 'cs' => 'DeviceRGB', 'filter' => 'FlateDecode', 'data' => gzcompress($rgb),
            'smask' => $hasAlpha ? gzcompress($alpha) : null];
    }

    public function output(): string
    {
        if ($this->footer && $this->page > 0) {
            ($this->footer)($this);
        }
        $nb = (string)$this->page;
        $objs = [];
        $objs[1] = null; // catalog
        $objs[2] = null; // pages
        $fontRefs = '';
        $n = 3;
        foreach (self::FONT_KEYS as $base => $key) {
            $objs[$n] = '<< /Type /Font /Subtype /Type1 /BaseFont /' . $base . ' /Encoding /WinAnsiEncoding >>';
            $fontRefs .= '/' . $key . ' ' . $n . ' 0 R ';
            $n++;
        }
        $xobj = '';
        foreach ($this->images as $img) {
            $smaskRef = '';
            if ($img['smask'] !== null) {
                $objs[$n] = ['dict' => sprintf('<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceGray /BitsPerComponent 8 /Filter /FlateDecode /Length %d >>', $img['w'], $img['h'], strlen($img['smask'])), 'stream' => $img['smask']];
                $smaskRef = ' /SMask ' . $n . ' 0 R';
                $n++;
            }
            $objs[$n] = ['dict' => sprintf('<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /%s /BitsPerComponent 8 /Filter /%s%s /Length %d >>', $img['w'], $img['h'], $img['cs'], $img['filter'], $smaskRef, strlen($img['data'])), 'stream' => $img['data']];
            $xobj .= '/I' . $img['n'] . ' ' . $n . ' 0 R ';
            $n++;
        }
        $resources = '<< /Font << ' . $fontRefs . '>> /XObject << ' . $xobj . '>> >>';
        $kids = [];
        foreach ($this->pages as $content) {
            $content = str_replace('{nb}', $nb, $content);
            $c = gzcompress($content);
            $objs[$n] = ['dict' => '<< /Filter /FlateDecode /Length ' . strlen($c) . ' >>', 'stream' => $c];
            $contentRef = $n; $n++;
            $objs[$n] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources %s /Contents %d 0 R >>', $this->w * $this->k, $this->h * $this->k, $resources, $contentRef);
            $kids[] = $n . ' 0 R'; $n++;
        }
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        $info = $n;
        $extra = '';
        foreach (($this->meta['Custom'] ?? []) as $k => $v) $extra .= ' /' . preg_replace('/[^A-Za-z0-9]/', '', $k) . ' (' . $this->escape(self::enc((string)$v)) . ')';
        $objs[$info] = sprintf('<< /Producer (Plataforma de Accionistas CONSA) /Title (%s) /Author (%s) /Subject (%s) /CreationDate (D:%s)%s >>',
            $this->escape(self::enc($this->meta['Title'])), $this->escape(self::enc($this->meta['Author'])), $this->escape(self::enc($this->meta['Subject'])), date('YmdHis'), $extra);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        ksort($objs);
        foreach ($objs as $id => $o) {
            $offsets[$id] = strlen($pdf);
            if (is_array($o)) {
                $pdf .= $id . " 0 obj\n" . $o['dict'] . "\nstream\n" . $o['stream'] . "\nendstream\nendobj\n";
            } else {
                $pdf .= $id . " 0 obj\n" . $o . "\nendobj\n";
            }
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($objs as $id => $_) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R /Info $info 0 R >>\nstartxref\n$xref\n%%EOF";
        return $pdf;
    }
}
