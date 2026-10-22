<?php
declare(strict_types=1);
require_once __DIR__ . '/libro.php';

/** Número en letras, género femenino (acciones): 21 → «veintiuna», 1.000.000 → «un millón». */
function numero_letras_fem(int $n): string
{
    if ($n === 0) return 'cero';
    $u = ['', 'una', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve',
        'veinte', 'veintiuna', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];
    $d = ['', '', '', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    $c = ['', 'ciento', 'doscientas', 'trescientas', 'cuatrocientas', 'quinientas', 'seiscientas', 'setecientas', 'ochocientas', 'novecientas'];
    $sub1000 = function (int $x, bool $fem = true) use ($u, $d, $c): string {
        if ($x === 100) return 'cien';
        $out = [];
        if ($x >= 100) { $out[] = $c[intdiv($x, 100)]; $x %= 100; }
        if ($x > 0) {
            if ($x < 30) $out[] = $u[$x];
            else { $t = $d[intdiv($x, 10)]; $r = $x % 10; $out[] = $r ? $t . ' y ' . $u[$r] : $t; }
        }
        $s = implode(' ', $out);
        if (!$fem) $s = preg_replace(['/\buna$/', '/veintiuna$/', '/ientas\b/'], ['un', 'veintiún', 'ientos'], $s);
        return $s;
    };
    $parts = [];
    $mill = intdiv($n, 1000000);
    $rest = $n % 1000000;
    if ($mill > 0) {
        $parts[] = $mill === 1 ? 'un millón' : $sub1000($mill, false) . ' millones';
    }
    $miles = intdiv($rest, 1000);
    $unid = $rest % 1000;
    if ($miles > 0) $parts[] = $miles === 1 ? 'mil' : $sub1000($miles) . ' mil';
    if ($unid > 0) $parts[] = $sub1000($unid);
    $s = implode(' ', $parts);
    if ($mill > 0 && $rest === 0) $s .= ' de';
    return $s;
}

function fecha_larga(string $ymd): string
{
    $m = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $t = strtotime($ymd);
    return (int)date('j', $t) . ' de ' . $m[(int)date('n', $t)] . ' de ' . date('Y', $t);
}

const URL_VALIDA_FIRMAYA = 'https://firmaya.idok.cl/doc_validator';
const URL_VALIDA_RES = 'https://www.registrodeempresasysociedades.cl/VerificarCertificados.aspx';
const URL_VALIDA_PORTAL = 'https://socialis.grupoconsa.cl/';

/**
 * Genera el PDF del certificado de accionista a partir del registro guardado ($cert con 'datos' JSON).
 */
function build_certificate_pdf(array $cert, array $company): string
{
    $D = json_decode($cert['datos'], true);
    $isRes = $cert['regimen'] === 'RES';
    $c1 = valid_hex($company['color1_hex']) ? $company['color1_hex'] : '#820503';
    $c2 = valid_hex($company['color2_hex']) ? $company['color2_hex'] : '#FE4101';
    $ink = [30, 32, 38];
    $muted = [110, 104, 92];
    $paper = tint($c2, 0.9);
    $boxFill = tint($c2, 0.94);
    $logo = $company['logo_path'] && is_file(STORAGE . '/' . $company['logo_path']) ? STORAGE . '/' . $company['logo_path'] : null;

    $p = new SimplePdf('P');
    $p->bMargin = 0;
    $p->meta = ['Title' => 'Certificado de accionistas N° ' . $cert['numero'], 'Author' => $company['razon_social'],
        'Subject' => 'Certificado ' . $cert['numero'] . ' · Código ' . $cert['codigo'],
        'Custom' => ['CertNumero' => $cert['numero'], 'CertCodigo' => $cert['codigo']]];
    $p->addPage();
    $W = 210; $H = 297;

    // Fondo
    $p->setFillColor($paper);
    $p->rect(0, 0, $W, $H, 'F');

    // Roseta de fondo (guilloché)
    $p->setDrawColor(tint($c1, 0.82));
    $p->setLineWidth(0.12);
    $cx = 105; $cy = 150;
    for ($k = 0; $k < 18; $k++) {
        $pts = [];
        $ph = $k * M_PI / 9;
        for ($t = 0; $t <= 360; $t += 2) {
            $a = deg2rad($t);
            $r = 42 + 16 * sin(7 * $a + $ph);
            $pts[] = [$cx + $r * cos($a + $ph / 7), $cy + $r * 0.95 * sin($a + $ph / 7)];
        }
        $p->polyline($pts, true);
    }

    // Marco exterior y banda decorativa
    $p->setDrawColor($c1);
    $p->setLineWidth(0.9);
    $p->rect(5.5, 5.5, $W - 11, $H - 11);
    $p->setLineWidth(0.3);
    $p->rect(7.5, 7.5, $W - 15, $H - 15);
    $p->rect(16, 16, $W - 32, $H - 32);
    $p->setDrawColor($c2);
    $p->setLineWidth(0.2);
    $p->rect(17.3, 17.3, $W - 34.6, $H - 34.6);
    // Ondas de la banda
    $wave = function (float $x1, float $x2, float $yMid, bool $horizontal) use ($p) {
        foreach ([0, M_PI / 2, M_PI] as $ph) {
            $pts = [];
            for ($s = $x1; $s <= $x2; $s += 0.35) {
                $o = 3.1 * sin($s * 2.1 + $ph);
                $pts[] = $horizontal ? [$s, $yMid + $o] : [$yMid + $o, $s];
            }
            $p->polyline($pts);
        }
    };
    $p->setDrawColor($c2);
    $p->setLineWidth(0.15);
    $wave(16, $W - 16, 11.75, true);
    $wave(16, $W - 16, $H - 11.75, true);
    $wave(16, $H - 16, 11.75, false);
    $wave(16, $H - 16, $W - 11.75, false);
    // Esquinas
    foreach ([[7.5, 7.5], [$W - 16, 7.5], [7.5, $H - 16], [$W - 16, $H - 16]] as [$x, $y]) {
        $p->setFillColor($paper);
        $p->setDrawColor($c1);
        $p->setLineWidth(0.3);
        $p->rect($x, $y, 8.5, 8.5, 'FD');
        $p->setFillColor($c1);
        $p->polyline([[$x + 4.25, $y + 2.2], [$x + 6.3, $y + 4.25], [$x + 4.25, $y + 6.3], [$x + 2.2, $y + 4.25]], true, 'F');
    }

    // Recuadros superiores
    $topBox = function (float $x, string $label, string $value) use ($p, $c1, $c2, $paper, $ink) {
        $p->setFillColor($paper);
        $p->setDrawColor($c2); $p->setLineWidth(0.25);
        $p->rect($x, 21, 40, 17, 'FD');
        $p->setDrawColor($c1); $p->setLineWidth(0.4);
        $p->rect($x + 1, 22, 38, 15);
        $p->setTextColor($c2);
        $p->setFont('B', 6.5, 'helvetica'); $p->charSpacing = 1.2;
        $p->textCenter($x + 20, 27, $label);
        $p->charSpacing = 0.3;
        $p->setTextColor($c1);
        $p->setFont('B', 12, 'times');
        $p->textCenter($x + 20, 34, $value);
        $p->charSpacing = 0;
    };
    $topBox(26, 'CERTIFICADO N°', str_replace('-', ' – ', $cert['numero']));
    $topBox($W - 66, 'ACCIONES', fnum($cert['cantidad']));

    // Medallón con logotipo
    $p->setDrawColor($c1); $p->setLineWidth(0.35);
    $p->circle(105, 30, 12.5);
    $zig = [];
    for ($i = 0; $i <= 96; $i++) { $a = 2 * M_PI * $i / 96; $r = $i % 2 ? 10.3 : 11.8; $zig[] = [105 + $r * cos($a), 30 + $r * sin($a)]; }
    $p->setDrawColor($c2); $p->setLineWidth(0.18);
    $p->polyline($zig, true);
    $p->setFillColor([255, 255, 255]);
    $p->setDrawColor($c1);
    $p->circle(105, 30, 9.8, 'FD');
    if ($logo) {
        $p->imageFit($logo, 97.5, 25.5, 15, 9);
    } else {
        $p->setTextColor($c1); $p->setFont('B', 7, 'times');
        $p->textCenter(105, 31, mb_strtoupper(mb_substr($company['nombre_fantasia'] ?: $company['razon_social'], 0, 14)));
    }

    // Razón social y datos
    $p->setTextColor($c1);
    $rs = '[ ' . mb_strtoupper($company['razon_social']) . ' ]';
    $size = 14;
    $p->charSpacing = 1.2;
    do { $p->setFont('B', $size, 'times'); $size -= 0.5; } while ($p->textWidth($rs) > 160 && $size > 8);
    $p->textCenter(105, 47, $rs);
    $p->charSpacing = 0.3;
    $p->setFont('I', 8.3, 'times');
    $p->setTextColor($muted);
    $dom = implode(', ', array_filter([$company['domicilio'], $company['comuna'], $company['ciudad']]));
    $p->textCenter(105, 52.5, 'R.U.T. ' . rut_format($company['rut']) . '  ·  Domicilio ' . $dom);

    // Título
    $p->charSpacing = 1.4;
    $p->setTextColor($c1);
    $p->setFont('B', 37, 'times');
    $p->textCenter(105, 68, 'CERTIFICADO');
    $p->charSpacing = 2;
    $p->setTextColor($c2);
    $p->setFont('B', 19, 'times');
    $p->textCenter(105, 77, 'DE ACCIONISTAS');
    $p->charSpacing = 0;
    $yOrn = 81.5;
    if ($isRes) {
        $p->setFont('B', 7.5, 'helvetica'); $p->charSpacing = 1.1;
        $p->setTextColor($c1);
        $p->textCenter(105, 81.3, 'DOCUMENTO DE CONTROL INTERNO — RÉGIMEN RES');
        $p->charSpacing = 0;
        $yOrn = 84;
    }
    $p->setDrawColor($c2); $p->setLineWidth(0.3);
    $p->line(62, $yOrn, 96, $yOrn); $p->line(114, $yOrn, 148, $yOrn);
    $p->setFillColor($c1);
    $p->polyline([[105, $yOrn - 1.6], [108.5, $yOrn], [105, $yOrn + 1.6], [101.5, $yOrn]], true, 'F');
    $p->setFillColor($c2);
    $p->circle(98, $yOrn, 0.6, 'F'); $p->circle(112, $yOrn, 0.6, 'F');

    $p->setTextColor($c1);
    $p->setFont('I', 14.5, 'times');
    $p->textCenter(105, $yOrn + 8, 'La sociedad emisora certifica que');

    // Titular
    $lbl = function (float $y, string $t) use ($p, $muted) {
        $p->setFont('', 5.6, 'helvetica'); $p->charSpacing = 0.9; $p->setTextColor($muted);
        $p->textCenter(105, $y, $t);
        $p->charSpacing = 0;
    };
    $y = $yOrn + 17.5;
    $p->setTextColor($ink);
    $name = $D['titular']['nombre'];
    $size = 15;
    do { $p->setFont('B', $size, 'times'); $size -= 0.5; } while ($p->textWidth($name) > 146 && $size > 9);
    $p->textCenter(105, $y, $name);
    $p->setDrawColor($c1); $p->setLineWidth(0.25);
    $p->line(30, $y + 2, 180, $y + 2);
    $lbl($y + 5.2, 'NOMBRE O RAZÓN SOCIAL DEL ACCIONISTA');
    $y += 10.5;
    $p->setTextColor($ink); $p->setFont('B', 12, 'times');
    $p->textCenter(105, $y, rut_format($D['titular']['rut']));
    $p->line(78, $y + 2, 132, $y + 2);
    $lbl($y + 5.2, 'R.U.T. DEL ACCIONISTA');

    $y += 10.5;
    $p->setTextColor($ink); $p->setFont('', 12, 'times');
    $p->textCenter(105, $y, 'figura inscrito en el Registro de Accionistas de la sociedad como titular de');
    $y += 9;
    $p->setFont('B', 13, 'times');
    $p->textCenter(55, $y, fnum($cert['cantidad']));
    $p->line(32, $y + 2, 78, $y + 2);
    $letras = numero_letras_fem((int)$cert['cantidad']);
    $size = 11;
    do { $p->setFont('I', $size, 'times'); $size -= 0.5; } while ($p->textWidth($letras) > 92 && $size > 7);
    $p->textCenter(131, $y, $letras);
    $p->setFont('', 13, 'times');
    $p->text(81.5, $y, '(');
    $p->text(178, $y, ')');
    $p->line(84, $y + 2, 177, $y + 2);
    $p->setFont('', 5.6, 'helvetica'); $p->charSpacing = 0.9; $p->setTextColor($muted);
    $p->textCenter(55, $y + 5.2, 'N° DE ACCIONES');
    $p->textCenter(131, $y + 5.2, 'CANTIDAD EN LETRAS');
    $p->charSpacing = 0;

    // Frase con serie y votos (segmentos con distinta fuente)
    $y += 11.5;
    $serie = $D['serie']['nombre'];
    $serieTxt = stripos($serie, 'serie') === 0 ? $serie : 'serie ' . $serie;
    $votos = rtrim(rtrim(number_format((float)$cert['votos_por_accion'], 4, ',', '.'), '0'), ',');
    $segs = [['', 'acciones nominativas de la '], ['B', $serieTxt], ['', ', sin valor nominal, con '], ['B', $votos], ['', ' voto(s) por acción']];
    $tw = 0;
    foreach ($segs as [$st, $t]) { $p->setFont($st, 12, 'times'); $tw += $p->textWidth($t); }
    $x = 105 - $tw / 2;
    $p->setTextColor($ink);
    foreach ($segs as [$st, $t]) {
        $p->setFont($st, 12, 'times');
        $p->text($x, $y, $t);
        $w = $p->textWidth($t);
        if ($st === 'B') { $p->setDrawColor($c1); $p->line($x - 1, $y + 1.6, $x + $w + 1, $y + 1.6); }
        $x += $w;
    }
    $y += 6;
    $p->setFont('', 12, 'times');
    $p->textCenter(105, $y, 'y los derechos y preferencias que establecen los estatutos sociales.');

    // Recuadros I y II
    $box = function (float $x, float $y, float $w, float $h, string $title) use ($p, $c1, $c2, $boxFill) {
        $p->setFillColor($boxFill);
        $p->setDrawColor($c2); $p->setLineWidth(0.3);
        $p->rect($x, $y, $w, $h, 'FD');
        $p->setDrawColor($c1); $p->setLineWidth(0.35);
        $p->rect($x + 0.9, $y + 0.9, $w - 1.8, $h - 1.8);
        $p->setTextColor($c1);
        $p->setFont('B', 7.5, 'helvetica'); $p->charSpacing = 1.3;
        $p->textCenter($x + $w / 2, $y + 6, $title);
        $p->charSpacing = 0;
        $p->setDrawColor($c2); $p->setLineWidth(0.2);
        $p->line($x + 5, $y + 8, $x + $w - 5, $y + 8);
    };
    $chk = function (float $x, float $y, bool $on) use ($p, $c1, $ink) {
        $p->setDrawColor($ink); $p->setLineWidth(0.25);
        $p->setFillColor([255, 255, 255]);
        $p->rect($x, $y - 2.7, 3, 3, 'FD');
        if ($on) {
            $p->setDrawColor($c1); $p->setLineWidth(0.5);
            $p->line($x + 0.5, $y - 2.2, $x + 2.5, $y - 0.2); $p->line($x + 2.5, $y - 2.2, $x + 0.5, $y - 0.2);
        }
    };
    $bY = $y + 4.5;
    $bH = 34;
    $box(23, $bY, 81, $bH, 'I  ESTADO DE PAGO');
    $ep = $cert['estado_pago'];
    $ty = $bY + 12.8;
    $p->setTextColor($ink);
    $chk(28, $ty, $ep === 'INTEGRAS'); $p->setFont('', 10.5, 'times'); $p->text(33, $ty, 'Íntegramente pagadas');
    $ty += 5.4;
    $chk(28, $ty, $ep === 'PARCIAL'); $p->setFont('', 10.5, 'times'); $p->text(33, $ty, 'Parcialmente pagadas');
    $ty += 4;
    $p->setFont('', 8.5, 'times');
    $p->text(33, $ty, 'Pagado $ ' . ($ep === 'PARCIAL' ? fnum($cert['monto_pagado']) : '________') . '   de $ ' . ($ep === 'PARCIAL' ? fnum($cert['monto_total']) : '________'));
    $ty += 5.4;
    $chk(28, $ty, $ep === 'NO_PAGADAS'); $p->setFont('', 10.5, 'times'); $p->text(33, $ty, 'No pagadas');
    $ty += 4;
    $p->setFont('', 8.5, 'times');
    $p->text(33, $ty, 'Plazo de entero ' . ($ep === 'NO_PAGADAS' || ($ep === 'PARCIAL' && $cert['plazo_entero']) ? fdate($cert['plazo_entero']) : '________________'));

    $box(106, $bY, 81, $bH, 'II  GRAVÁMENES');
    $p->setTextColor($ink);
    $gx = 111; $gy = $bY + 14;
    if ($isRes) {
        $p->setFont('I', 9.3, 'times');
        $p->x = 109; $p->y = $bY + 10.5; $p->lMargin = 109;
        $p->multiCell(75, 4.1, 'Tratándose de una sociedad inscrita en el Registro de Empresas y Sociedades (RES), no es posible certificar la existencia o inexistencia de gravámenes sobre estas acciones. Ello debe ser verificado en el portal del Registro de Empresas y Sociedades.', 'C');
        $p->lMargin = 15;
    } else {
        $gr = $D['gravamenes'] ?? [];
        $gy = $bY + 12.3;
        $chk($gx, $gy, !$gr); $p->setFont('', 9.8, 'times');
        $p->text($gx + 5, $gy, 'Libres de gravámenes, prohibiciones y');
        $p->text($gx + 5, $gy + 3.9, 'embargos informados a la sociedad');
        $gy += 8.7;
        $chk($gx, $gy, (bool)$gr); $p->setFont('', 9.8, 'times');
        $p->text($gx + 5, $gy, 'Con gravamen informado a la sociedad');
        $gy += 3.9;
        $p->setFont('', 8, 'times');
        if ($gr) {
            $nat = implode('; ', array_map(fn($g) => $g['tipo'] . ' (' . fnum($g['cantidad']) . ' acc.)', $gr));
            $fav = implode('; ', array_unique(array_column($gr, 'a_favor_de')));
            $fec = implode('; ', array_map(fn($g) => fdate($g['fecha_anotacion']), $gr));
            foreach ([['Naturaleza', $nat], ['A favor de', $fav], ['Anotado el', $fec]] as [$k, $v]) {
                $v = $p->wrap($v, 52)[0] . (count($p->wrap($v, 52)) > 1 ? '…' : '');
                $p->text($gx + 5, $gy, $k . ': ' . $v);
                $gy += 3.2;
            }
        } else {
            foreach (['Naturaleza', 'A favor de', 'Anotado el'] as $k) { $p->text($gx + 5, $gy, $k . ' ______________________'); $gy += 3.2; }
        }
    }

    // Recuadro III
    $y3 = $bY + $bH + 3;
    $box(23, $y3, 164, 24.5, 'III  ORIGEN DE LA TITULARIDAD');
    $p->setFont('B', 6.3, 'helvetica'); $p->charSpacing = 0.8; $p->setTextColor($c2);
    $p->text(28, $y3 + 12, 'N° CERTIFICADO DE COMPRA DE ACCIONES');
    $p->text(116, $y3 + 12, 'CVE · SOCIEDADES EN RÉGIMEN RES');
    $p->charSpacing = 0;
    $p->setTextColor($c2); $p->setFont('I', 10, 'times');
    $p->textCenter(105, $y3 + 15, 'o bien');
    $p->setTextColor($ink);
    $origen = $D['origen'] ?? [];
    $fitText = function (string $t, float $x, float $y, float $w, float $size) use ($p) {
        do { $p->setFont('', $size, 'times'); $size -= 0.3; } while ($p->textWidth($t) > $w && $size > 6);
        $p->text($x, $y, $t);
    };
    if ($isRes) {
        $fitText('No aplica (régimen RES)', 28, $y3 + 16.3, 62, 9.5);
        $cves = $origen['cves'] ?? [];
        $t = $cert['res_pendiente'] ? 'Registro RES pendiente' . ($cves ? ' · ' . implode(', ', $cves) : '') : ('Código de Verificación Electrónica ' . implode(', ', $cves));
        if ($cert['res_pendiente']) { $p->setTextColor([160, 40, 40]); }
        $fitText($t, 116, $y3 + 16.3, 67, 9.5);
        $p->setTextColor($ink);
    } else {
        $fitText(implode(', ', $origen['folios'] ?? []), 28, $y3 + 16.3, 62, 9.5);
        $fitText('No aplica (régimen tradicional)', 116, $y3 + 16.3, 67, 9.5);
    }
    $p->setDrawColor($c2); $p->setLineWidth(0.2);
    $p->line(28, $y3 + 18, 182, $y3 + 18);
    $ins = $D['inscripcion'];
    $p->setFont('', 9, 'times');
    $p->textCenter(105, $y3 + 22, 'Inscripción en el Registro de Accionistas: Folio ' . $ins['folio'] . '    Fecha ' . fdate($ins['fecha']) . '    Código de verificación ' . $cert['codigo']);

    // Testimonio
    $yt = max(220.5, $y3 + 30.5);
    $p->setTextColor($ink);
    $p->setFont('I', 11.5, 'times');
    $p->textCenter(105, $yt, 'En testimonio de lo cual se extiende el presente certificado, en ' . $cert['lugar'] . ',');
    $p->textCenter(105, $yt + 5.5, 'a ' . fecha_larga($cert['fecha_emision']) . '.');

    // Sello
    $sy = $yt + 18.5; $sx = 105;
    $p->setFillColor($c1);
    $p->polyline([[$sx - 5, $sy + 6], [$sx - 7.5, $sy + 15.8], [$sx - 5.3, $sy + 14.3], [$sx - 3.4, $sy + 16.5], [$sx - 1.5, $sy + 8]], true, 'F');
    $p->polyline([[$sx + 5, $sy + 6], [$sx + 7.5, $sy + 15.8], [$sx + 5.3, $sy + 14.3], [$sx + 3.4, $sy + 16.5], [$sx + 1.5, $sy + 8]], true, 'F');
    $star = [];
    for ($i = 0; $i < 64; $i++) { $a = 2 * M_PI * $i / 64; $r = $i % 2 ? 11 : 12.2; $star[] = [$sx + $r * cos($a), $sy + $r * sin($a)]; }
    $p->setFillColor($c2);
    $p->polyline($star, true, 'F');
    $p->setDrawColor(tint($c2, 0.5)); $p->setLineWidth(0.2);
    $p->circle($sx, $sy, 9.3);
    for ($k = 0; $k < 12; $k++) { $p->circle($sx + 3.2 * cos($k * M_PI / 6), $sy + 3.2 * sin($k * M_PI / 6), 3.6, 'D', 36); }
    $p->setFillColor(tint($c2, 0.35));
    $p->circle($sx, $sy, 2.4, 'F');

    // Validación (izquierda) y firma (derecha)
    $vy = $yt + 12;
    $p->setTextColor($c1); $p->setFont('B', 7, 'helvetica'); $p->charSpacing = 1;
    $p->text(25, $vy, 'VALIDACIÓN');
    $p->charSpacing = 0;
    $p->setDrawColor($c1); $p->setLineWidth(0.25);
    $p->line(25, $vy + 1.5, 88, $vy + 1.5);
    $p->setTextColor($ink); $p->setFont('', 6.8, 'helvetica');
    $p->x = 25; $p->y = $vy + 2.5; $p->lMargin = 25;
    $lines = ['Firma electrónica: ' . URL_VALIDA_FIRMAYA];
    if ($isRes) $lines[] = 'CVE del RES: ' . URL_VALIDA_RES;
    else $lines[] = 'Folio del certificado de venta: ' . URL_VALIDA_PORTAL;
    foreach ($lines as $l) $p->multiCell(64, 3.2, $l);
    $p->lMargin = 15;

    $fx = 124; $fy = $yt + 23.5;
    $p->setDrawColor($c1); $p->setLineWidth(0.3);
    $p->line($fx, $fy, $fx + 63, $fy);
    $p->setTextColor($c1); $p->setFont('B', 8, 'helvetica'); $p->charSpacing = 1.2;
    $p->textCenter($fx + 31.5, $fy + 4.5, 'REPRESENTANTE LEGAL');
    $p->charSpacing = 0;
    $p->setTextColor($muted); $p->setFont('I', 8.5, 'times');
    $rep = $D['representante'];
    $p->textCenter($fx + 31.5, $fy + 8.3, $rep['nombre']);
    $p->textCenter($fx + 31.5, $fy + 11.6, 'R.U.T. ' . rut_format($rep['rut']) . ' · ' . $rep['cargo']);

    // Leyendas finales
    $ly = $yt + 36.5;
    $p->setFillColor(tint($c1, 0.92));
    $p->setDrawColor($c1); $p->setLineWidth(0.3);
    $p->rect(30, $ly, 150, 7.5, 'FD');
    $p->setTextColor($c1); $p->setFont('B', 7.6, 'helvetica');
    $p->textCenter(105, $ly + 3.2, 'DOCUMENTO VÁLIDO SOLO CON FIRMA ELECTRÓNICA DEL REPRESENTANTE LEGAL.');
    $p->setFont('', 6.8, 'helvetica');
    $p->textCenter(105, $ly + 6.2, 'Sin la firma electrónica de ' . $rep['nombre'] . ' este certificado no tiene validez.');
    $p->setTextColor($muted);
    $p->setFont('I', 7, 'times');
    $p->textCenter(105, $ly + 10.5, 'La transferencia de estas acciones sólo producirá efectos respecto de la sociedad y de terceros desde su inscripción en el Registro de Accionistas.');
    if ($isRes) {
        $p->setFont('BI', 7, 'times'); $p->setTextColor($c1);
        $p->textCenter(105, $ly + 13.8, 'Este certificado es únicamente para control interno de la compañía y no reemplaza el certificado de accionistas');
        $p->textCenter(105, $ly + 17.1, 'emitido por el portal del Registro de Empresas y Sociedades.');
    } else {
        $p->textCenter(105, $ly + 13.8, 'Este certificado acredita la titularidad inscrita a la fecha de su emisión y no constituye título negociable.');
    }
    $p->setTextColor($muted); $p->setFont('', 5.8, 'helvetica');
    $p->textCenter(105, $isRes ? $ly + 20.4 : $ly + 17.3, 'Certificado N° ' . $cert['numero'] . ' · Código de verificación ' . $cert['codigo'] . ' · Emitido el ' . fdate($cert['fecha_emision']) . ' · Plataforma de Accionistas CONSA');
    return $p->output();
}

/** Busca un texto en un PDF, también dentro de flujos comprimidos. */
function pdf_contains(string $raw, string $needle): bool
{
    if (str_contains($raw, $needle)) return true;
    if (!preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $m)) return false;
    foreach ($m[1] as $s) {
        $d = @zlib_decode($s);
        if ($d !== false && str_contains($d, $needle)) return true;
    }
    return false;
}

/** Verifica que el PDF traiga firma electrónica incrustada. */
function pdf_has_signature(string $raw): bool
{
    return (bool)preg_match('#/ByteRange\s*\[#', $raw) && (bool)preg_match('#/Type\s*/Sig\b|/SubFilter\s*/(adbe\.pkcs7|ETSI\.CAdES|adbe\.x509)#', $raw);
}
