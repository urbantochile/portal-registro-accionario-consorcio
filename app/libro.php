<?php
declare(strict_types=1);

/** Color de texto legible sobre un fondo HEX. */
function contrast_text(string $hex): array
{
    [$r, $g, $b] = SimplePdf::hexToRgb($hex);
    $l = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $l > 0.6 ? [20, 24, 32] : [255, 255, 255];
}
function tint(string $hex, float $f): array
{
    $c = SimplePdf::hexToRgb($hex);
    return array_map(fn($v) => (int)round($v + (255 - $v) * $f), $c);
}

/** Fila de tabla con ajuste de línea por celda. */
function pdf_row(SimplePdf $pdf, array $w, array $vals, array $align, float $lh = 4.2, ?array $fill = null, bool $bold = false, ?callable $onBreak = null): void
{
    $pdf->setFont($bold ? 'B' : '', $pdf->__fs ?? 7.5);
    $lines = [];
    $max = 1;
    foreach ($vals as $i => $v) {
        $lines[$i] = $pdf->wrap((string)$v, $w[$i] - 2.4);
        $max = max($max, count($lines[$i]));
    }
    $h = $max * $lh + 1.6;
    if ($pdf->y + $h > $pdf->pageHeight() - $pdf->bMargin) {
        $pdf->addPage();
        if ($onBreak) $onBreak($pdf);
        $pdf->setFont($bold ? 'B' : '', $pdf->__fs ?? 7.5);
    }
    $x = $pdf->lMargin;
    $y = $pdf->y;
    foreach ($vals as $i => $v) {
        if ($fill) { $pdf->setFillColor($fill); $pdf->rect($x, $y, $w[$i], $h, 'FD'); } else { $pdf->rect($x, $y, $w[$i], $h, 'D'); }
        $ly = $y + 0.8;
        foreach ($lines[$i] as $ln) {
            $pdf->x = $x; $pdf->y = $ly;
            $pdf->cell($w[$i], $lh, $ln, '', $align[$i] ?? 'L');
            $ly += $lh;
        }
        $x += $w[$i];
    }
    $pdf->x = $pdf->lMargin;
    $pdf->y = $y + $h;
}

function build_libro_pdf(array $c, string $folio): string
{
    $u = current_user();
    $c1 = valid_hex($c['color1_hex']) ? $c['color1_hex'] : '#820503';
    $c2 = valid_hex($c['color2_hex']) ? $c['color2_hex'] : '#FE4101';
    $t1 = contrast_text($c1);
    $logo = $c['logo_path'] && is_file(STORAGE . '/' . $c['logo_path']) ? STORAGE . '/' . $c['logo_path'] : null;
    $fechaEntrega = date('d-m-Y H:i');
    $entregado = 'Entregado a: ' . $c['rep_nombre'] . ', RUT ' . rut_format($c['rep_rut']) . ' (' . $c['rep_cargo'] . ') – ' . $fechaEntrega;

    $pdf = new SimplePdf('L');
    $pdf->meta = ['Title' => 'Libro de Accionistas ' . $c['razon_social'], 'Author' => $c['razon_social'], 'Subject' => 'Folio ' . $folio];
    $pdf->tMargin = 30; $pdf->bMargin = 16; $pdf->lMargin = 15; $pdf->rMargin = 15;
    $W = $pdf->pageWidth(); $H = $pdf->pageHeight();
    $cover = true;
    $pdf->header = function (SimplePdf $p) use (&$cover, $c, $c1, $c2, $t1, $logo, $W) {
        if ($cover) return;
        $p->setFillColor($c1);
        $p->rect(0, 0, $W, 4, 'F');
        $p->setFillColor($c2);
        $p->rect(0, 4, $W, 1, 'F');
        if ($logo) $p->imageFit($logo, 15, 8, 32, 15);
        $p->setTextColor($c1);
        $p->setFont('B', 13);
        $t = 'LIBRO DE ACCIONISTAS';
        $p->text($W - 15 - $p->textWidth($t), 14, $t);
        $p->setFont('', 8.5);
        $p->setTextColor([60, 66, 80]);
        $t = $c['razon_social'] . ' · RUT ' . rut_format($c['rut']);
        $p->text($W - 15 - $p->textWidth($t), 19.5, $t);
        $p->setDrawColor($c2);
        $p->setLineWidth(0.3);
        $p->line(15, 25, $W - 15, 25);
        $p->setDrawColor([150, 158, 172]);
        $p->setLineWidth(0.15);
    };
    $pdf->footer = function (SimplePdf $p) use ($folio, $entregado, $W, $H, $c1) {
        $p->setFont('', 7);
        $p->setTextColor([90, 96, 110]);
        $p->setDrawColor([200, 205, 214]);
        if ($p->pageNo() > 1) $p->line(15, $H - 11, $W - 15, $H - 11);
        $fx = $p->pageNo() === 1 ? 108 : 15;
        if ($p->pageNo() === 1) $p->line(108, $H - 11, $W - 15, $H - 11);
        $p->text($fx, $H - 7, ($p->pageNo() === 1 ? '' : 'Folio ' . $folio . '  ·  ') . $entregado);
        $t = 'Página ' . $p->pageNo() . ' de {nb}';
        $p->text($W - 15 - $p->textWidth($t) + 3, $H - 7, $t);
        $p->setDrawColor([150, 158, 172]);
    };

    // ---------------- Portada ----------------
    $pdf->addPage();
    $pdf->setFillColor($c1);
    $pdf->rect(0, 0, 92, $H, 'F');
    $pdf->setFillColor($c2);
    $pdf->rect(92, 0, 3, $H, 'F');
    if ($logo) {
        $pdf->setFillColor([255, 255, 255]);
        $pdf->rect(14, 22, 64, 40, 'F');
        $pdf->imageFit($logo, 18, 26, 56, 32);
    }
    $pdf->setTextColor($t1);
    $pdf->setFont('B', 9);
    $pdf->text(14, 80, 'FOLIO');
    $pdf->setFont('B', 12);
    $pdf->text(14, 87, $folio);
    $pdf->setFont('B', 9);
    $pdf->text(14, 102, 'FECHA DE EMISIÓN');
    $pdf->setFont('', 11);
    $pdf->text(14, 109, $fechaEntrega);
    $pdf->setFont('B', 9);
    $pdf->text(14, 124, 'RÉGIMEN');
    $pdf->setFont('', 9.5);
    $pdf->text(14, 131, 'Sistema registral tradicional');
    $pdf->text(14, 136, 'Registro de Comercio · CBR');
    $pdf->setFont('', 7);
    $pdf->x = 14; $pdf->y = $H - 40; $pdf->lMargin = 14;
    $pdf->multiCell(66, 3.4, 'Documento generado desde la Plataforma de Accionistas CONSA. Toda entrega queda registrada con folio, fecha y usuario en la bitácora del sistema.');
    $pdf->lMargin = 15;

    $x0 = 108;
    $pdf->setTextColor($c1);
    $pdf->setFont('B', 30);
    $pdf->text($x0, 45, 'LIBRO DE');
    $pdf->text($x0, 58, 'ACCIONISTAS');
    $pdf->setFillColor($c2);
    $pdf->rect($x0, 64, 40, 1.4, 'F');
    $pdf->setTextColor([20, 24, 32]);
    $pdf->setFont('B', 15);
    $pdf->x = $x0; $pdf->y = 70; $pdf->lMargin = $x0;
    $pdf->multiCell($W - $x0 - 15, 6.5, $c['razon_social']);
    $pdf->setFont('', 10);
    $pdf->setTextColor([60, 66, 80]);
    $pdf->multiCell($W - $x0 - 15, 5, 'RUT ' . rut_format($c['rut']) . ' · ' . (TIPOS_SOCIALES[$c['tipo_social']] ?? '') . ($c['nombre_fantasia'] ? ' · ' . $c['nombre_fantasia'] : ''));
    $pdf->y += 3;
    $info = [
        ['Constitución', 'Escritura pública de ' . fdate($c['fecha_escritura']) . ', ' . $c['notaria'] . ($c['repertorio'] ? ', repertorio N° ' . $c['repertorio'] : '') . '.'],
        ['Inscripción', 'Fojas ' . $c['fojas'] . ' N° ' . $c['numero_inscripcion'] . ' del Registro de Comercio del año ' . $c['anio_inscripcion'] . ', ' . $c['cbr'] . '.'],
        ['Publicación', 'Diario Oficial de ' . fdate($c['fecha_diario_oficial']) . '.'],
        ['Domicilio', implode(', ', array_filter([$c['domicilio'], $c['comuna'], $c['ciudad'], $c['region']]))],
        ['Capital social', $c['capital_social'] !== null ? '$' . fnum($c['capital_social']) . ($c['total_acciones_estatuto'] ? ', dividido en ' . fnum($c['total_acciones_estatuto']) . ' acciones' : '') : '—'],
        ['Representante legal', $c['rep_nombre'] . ', RUT ' . rut_format($c['rep_rut']) . ', ' . $c['rep_cargo'] . '. Poderes: ' . ($c['poderes_fuente'] === 'ACTA_DIRECTORIO' ? 'sesión de directorio' : 'certificado de poderes') . ' de ' . fdate($c['poderes_fecha']) . '.'],
    ];
    foreach ($info as [$k, $v]) {
        $pdf->setFont('B', 8.5);
        $pdf->setTextColor($c1);
        $pdf->text($x0, $pdf->y + 3.5, mb_strtoupper($k));
        $pdf->setFont('', 9.5);
        $pdf->setTextColor([30, 34, 44]);
        $pdf->x = $x0 + 38; $pdf->lMargin = $x0 + 38;
        $pdf->multiCell($W - $x0 - 15 - 38, 4.6, $v);
        $pdf->y += 1.6;
    }
    // Caja de entrega
    $by = max($pdf->y + 4, 150);
    $pdf->lMargin = 15;
    $pdf->setFillColor(tint($c1, 0.9));
    $pdf->setDrawColor($c1);
    $pdf->setLineWidth(0.4);
    $pdf->rect($x0, $by, $W - $x0 - 15, 34, 'FD');
    $pdf->setLineWidth(0.15);
    $pdf->setTextColor($c1);
    $pdf->setFont('B', 9);
    $pdf->text($x0 + 5, $by + 7, 'CONSTANCIA DE ENTREGA');
    $pdf->setTextColor([20, 24, 32]);
    $pdf->setFont('', 9.5);
    $pdf->x = $x0 + 5; $pdf->y = $by + 9.5; $pdf->lMargin = $x0 + 5;
    $pdf->multiCell($W - $x0 - 25, 4.6, 'Este Libro de Accionistas se entrega exclusivamente a ' . $c['rep_nombre'] . ', RUT ' . rut_format($c['rep_rut']) . ', en su calidad de ' . $c['rep_cargo'] . ' y representante legal de la sociedad, el ' . $fechaEntrega . ' horas, bajo el folio ' . $folio . '. Generado por ' . $u['nombre'] . ' (' . $u['email'] . ').');
    $pdf->lMargin = 15;
    $pdf->setDrawColor([120, 126, 140]);
    $pdf->line($x0 + 110, $by + 29, $W - 25, $by + 29);
    $pdf->setFont('', 7.5);
    $pdf->text($x0 + 110, $by + 32.5, 'Firma de recepción del representante legal');
    $pdf->setDrawColor([150, 158, 172]);

    // ---------------- Resumen ----------------
    $cover = false;
    $sh = current_shareholders((int)$c['id']);
    $issued = issued_by_series((int)$c['id']);
    $pdf->addPage();
    $pdf->setTextColor($c1);
    $pdf->setFont('B', 13);
    $pdf->text(15, $pdf->y + 5, 'Resumen de accionistas vigentes al ' . date('d-m-Y'));
    $pdf->y += 10;
    $pdf->setTextColor([20, 24, 32]);
    $nS = count($sh['series']);
    $wS = $nS ? min(28, 90 / $nS) : 0;
    $w = array_merge([12, 267 - 12 - 32 - $nS * $wS - 30 - 22 - 22, 32], array_fill(0, $nS, $wS), [30, 22, 22]);
    $head = array_merge(['N°', 'Accionista', 'RUT'], array_map(fn($s) => serie_label($s['nombre']), $sh['series']), ['Total acciones', '% capital', '% votos']);
    $al = array_merge(['C', 'L', 'L'], array_fill(0, $nS, 'R'), ['R', 'R', 'R']);
    $hdr = function (SimplePdf $p) use ($w, $head, $c1, $t1) {
        $p->setTextColor($t1); $p->setDrawColor($c1);
        pdf_row($p, $w, $head, array_fill(0, count($head), 'C'), 4, SimplePdf::hexToRgb($c1), true);
        $p->setTextColor([20, 24, 32]); $p->setDrawColor([150, 158, 172]);
    };
    $pdf->__fs = 8;
    $hdr($pdf);
    $i = 0;
    foreach ($sh['rows'] as $r) {
        $m = $r['member'];
        $vals = array_merge([str_pad((string)$m['socio_numero'], 2, '0', STR_PAD_LEFT), person_name($m), rut_format($m['rut'])],
            array_map(fn($s) => fnum($r['series'][(int)$s['id']] ?? 0), $sh['series']), [fnum($r['total']), fpct($r['pct']), fpct($r['pct_votos'])]);
        pdf_row($pdf, $w, $vals, $al, 4, ($i++ % 2) ? tint($c2, 0.88) : null, false, $hdr);
    }
    if (!$sh['rows']) pdf_row($pdf, [267], ['No hay accionistas vigentes.'], ['C']);
    $tot = array_merge(['', 'TOTAL', ''], array_map(fn($s) => fnum(array_sum(array_map(fn($r) => $r['series'][(int)$s['id']] ?? 0, $sh['rows']))), $sh['series']), [fnum($sh['total']), '100,00 %', '100,00 %']);
    pdf_row($pdf, $w, $tot, $al, 4, tint($c1, 0.8), true, $hdr);

    $pdf->y += 8;
    $pdf->checkPageBreak(30);
    $pdf->setTextColor($c1);
    $pdf->setFont('B', 11);
    $pdf->text(15, $pdf->y + 4, 'Series de acciones');
    $pdf->y += 7;
    $pdf->setTextColor([20, 24, 32]);
    $ws = [50, 35, 45, 45, 92];
    $sh2 = function (SimplePdf $p) use ($ws, $c1, $t1) {
        $p->setTextColor($t1); $p->setDrawColor($c1);
        pdf_row($p, $ws, ['Serie', 'Votos por acción', 'Acciones autorizadas', 'Acciones suscritas', 'Preferencias'], array_fill(0, 5, 'C'), 4, SimplePdf::hexToRgb($c1), true);
        $p->setTextColor([20, 24, 32]); $p->setDrawColor([150, 158, 172]);
    };
    $sh2($pdf);
    foreach ($sh['series'] as $s) {
        pdf_row($pdf, $ws, [$s['nombre'], rtrim(rtrim(number_format((float)$s['votos_por_accion'], 4, ',', '.'), '0'), ','), $s['acciones_autorizadas'] !== null ? fnum($s['acciones_autorizadas']) : 'Sin límite', fnum($issued[(int)$s['id']] ?? 0), (string)$s['preferencias']], ['L', 'R', 'R', 'R', 'L'], 4, null, false, $sh2);
    }

    // ---------------- Hojas por accionista ----------------
    $members = all('SELECT m.id AS mid, m.numero AS socio_n, m.fecha_ingreso, p.* FROM members m JOIN persons p ON p.id=m.person_id WHERE m.company_id=? ORDER BY m.numero', [$c['id']]);
    $cw = [20, 16, 12, 17, 35, 35, 17, 17, 19, 19, 21, 24, 15];
    $ch = ['Fecha de transferencia', 'N° transf.', 'Serie', 'N° del título inutilizado', 'Comprado a', 'Vendido a', 'N° título nuevo del comprador', 'N° título nuevo del vendedor', 'Compras', 'Ventas', 'Saldos', 'Observaciones', 'N° del título emitido'];
    $ca = ['C', 'C', 'C', 'C', 'L', 'L', 'C', 'C', 'R', 'R', 'R', 'L', 'C'];
    $sheetHdr = function (SimplePdf $p) use ($cw, $ch, $c1, $t1) {
        $p->__fs = 6.8;
        $p->setTextColor($t1); $p->setDrawColor($c1);
        pdf_row($p, $cw, $ch, array_fill(0, count($ch), 'C'), 3.2, SimplePdf::hexToRgb($c1), true);
        $p->setTextColor([20, 24, 32]); $p->setDrawColor([150, 158, 172]);
        $p->__fs = 7.5;
    };
    foreach ($members as $m) {
        $rows = shareholder_sheet((int)$c['id'], (int)$m['mid']);
        if (!$rows) continue; // socios que nunca han tenido acciones no tienen hoja
        $pdf->addPage();
        $saldo = end($rows)['saldo'];
        $pdf->setTextColor($c1);
        $pdf->setFont('B', 12);
        $pdf->text(15, $pdf->y + 4, 'REGISTRO DE ACCIONISTAS · Socio N° ' . str_pad((string)$m['socio_n'], 2, '0', STR_PAD_LEFT));
        $pdf->y += 8;
        // bloque de identificación (como el libro físico)
        $firma = one("SELECT * FROM documents WHERE owner_type='member' AND owner_id=? AND tipo='FIRMA' AND vigente=1 ORDER BY id DESC LIMIT 1", [$m['mid']]);
        $fields = [
            ['ACCIONISTA', person_name($m), 'DIRECCIÓN', trim(($m['calle'] ?? '') . ' ' . ($m['numero'] ?? '') . ($m['depto'] ? ', ' . $m['depto'] : '')) . ', ' . $m['comuna']],
            ['NACIONALIDAD', (string)$m['nacionalidad'], 'FONO', (string)$m['telefono']],
            ['R.U.T.', rut_format($m['rut']), 'CIUDAD', (string)$m['ciudad']],
        ];
        $y0 = $pdf->y;
        foreach ($fields as [$k1, $v1, $k2, $v2]) {
            $pdf->setFont('B', 7.5); $pdf->setTextColor([70, 76, 90]);
            $pdf->text(15, $pdf->y + 4, $k1);
            $pdf->text(150, $pdf->y + 4, $k2);
            $pdf->setFont('', 9); $pdf->setTextColor([20, 24, 32]);
            $pdf->x = 42; $pdf->cell(100, 5.5, $v1);
            $pdf->x = 170; $pdf->cell(95, 5.5, $v2);
            $pdf->setDrawColor([170, 176, 188]);
            $pdf->line(42, $pdf->y + 5.5, 142, $pdf->y + 5.5);
            $pdf->line(170, $pdf->y + 5.5, 282, $pdf->y + 5.5);
            $pdf->y += 7;
        }
        $pdf->setFont('B', 7.5); $pdf->setTextColor([70, 76, 90]);
        $pdf->text(15, $pdf->y + 4, 'FIRMA ACCIONISTA');
        if ($firma && str_starts_with($firma['mime'], 'image/') && is_file(STORAGE . '/' . $firma['ruta'])) {
            $pdf->imageFit(STORAGE . '/' . $firma['ruta'], 48, $pdf->y - 3, 45, 11);
        } else {
            $pdf->setFont('', 8); $pdf->setTextColor([120, 126, 140]);
            $pdf->text(48, $pdf->y + 4, $firma ? 'Firma registrada en archivo PDF' : 'Sin firma registrada');
        }
        $pdf->setDrawColor([170, 176, 188]);
        $pdf->line(48, $pdf->y + 5.5, 142, $pdf->y + 5.5);
        $pdf->setFont('B', 7.5); $pdf->setTextColor([70, 76, 90]);
        $pdf->text(150, $pdf->y + 4, 'SALDO ACTUAL');
        $pdf->setFont('B', 11); $pdf->setTextColor($saldo > 0 ? $c1 : [150, 40, 40]);
        $pdf->text(176, $pdf->y + 4.5, fnum($saldo) . ' acciones' . ($saldo > 0 ? '' : ' · enajenó la totalidad'));
        $pdf->y += 10;
        $pdf->setTextColor([20, 24, 32]);
        $pdf->setDrawColor([150, 158, 172]);
        $sheetHdr($pdf);
        $i = 0;
        foreach ($rows as $r) {
            pdf_row($pdf, $cw, [fdate($r['fecha']), (string)$r['numero'], $r['serie'], (string)$r['titulo_inutilizado'], $r['comprado_a'], $r['vendido_a'], (string)$r['titulo_nuevo_comprador'], (string)$r['titulo_nuevo_vendedor'],
                $r['compras'] ? fnum($r['compras']) : '', $r['ventas'] ? fnum($r['ventas']) : '', fnum($r['saldo']), (string)$r['observaciones'], (string)$r['titulo_emitido']], $ca, 3.6, ($i++ % 2) ? tint($c2, 0.9) : null, false, $sheetHdr);
        }
        $notes = all('SELECT * FROM member_notes WHERE member_id=? ORDER BY fecha, id', [$m['mid']]);
        if ($notes) {
            $pdf->y += 4;
            $pdf->checkPageBreak(12);
            $pdf->setFont('B', 8); $pdf->setTextColor($c1);
            $pdf->text(15, $pdf->y + 3, 'Anotaciones del registro de socios');
            $pdf->y += 5;
            $pdf->setFont('', 7.8); $pdf->setTextColor([30, 34, 44]);
            foreach ($notes as $n) $pdf->multiCell(267, 3.8, fdate($n['fecha']) . ' — ' . $n['texto']);
        }
    }
    // Cierre
    $pdf->y += 8;
    $pdf->checkPageBreak(20);
    $pdf->setFont('', 7.5);
    $pdf->setTextColor([90, 96, 110]);
    $pdf->multiCell(267, 3.8, 'Fin del Libro de Accionistas folio ' . $folio . '. Se incluyen las hojas de todos los socios que han registrado movimientos de acciones, con sus saldos calculados automáticamente a partir de las transferencias vigentes (las anuladas se excluyen).');
    return $pdf->output();
}
