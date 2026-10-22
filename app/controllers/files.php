<?php
declare(strict_types=1);

function send_file(string $path, string $mime, string $name, bool $inline = true): never
{
    if (!is_file($path)) { http_response_code(404); exit('Archivo no encontrado.'); }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
    header('Cache-Control: private, no-store');
    readfile($path);
    exit;
}

function ctl_doc_index(): void
{
    require_login();
    $d = one('SELECT * FROM documents WHERE id=?', [(int)getp('id')]);
    if (!$d) { http_response_code(404); exit('Documento no encontrado.'); }
    switch ($d['owner_type']) {
        case 'company':
            require_perm('sociedades');
            require_company((int)$d['owner_id']);
            break;
        case 'person':
            require_perm('socios');
            break;
        case 'member':
            require_perm('socios');
            require_company((int)val('SELECT company_id FROM members WHERE id=?', [$d['owner_id']]));
            break;
        case 'certificate':
            $ce = one('SELECT * FROM certificates WHERE id=?', [$d['owner_id']]);
            require_company((int)$ce['company_id']);
            require_perm($d['tipo'] === 'CERT_FIRMADO' ? 'certificados' : 'cert_sin_firma');
            break;
        case 'gravamen':
            require_perm('gravamenes');
            require_company((int)val('SELECT company_id FROM gravamenes WHERE id=?', [$d['owner_id']]));
            break;
        case 'movement':
            require_perm('accionistas');
            require_company((int)val('SELECT company_id FROM movements WHERE id=?', [$d['owner_id']]));
            break;
        default:
            http_response_code(403); exit;
    }
    audit('DOC_CONSULTADO', 'document', (int)$d['id'], null, ['tipo' => $d['tipo'], 'archivo' => $d['nombre_original']]);
    send_file(STORAGE . '/' . $d['ruta'], $d['mime'], $d['nombre_original']);
}

function ctl_doc_logo(): void
{
    require_login();
    $c = require_company((int)getp('c'));
    if (!$c['logo_path']) { http_response_code(404); exit; }
    $p = STORAGE . '/' . $c['logo_path'];
    send_file($p, (new finfo(FILEINFO_MIME_TYPE))->file($p), 'logo');
}
