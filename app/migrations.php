<?php
declare(strict_types=1);

/**
 * Migraciones de base de datos. La versión instalada se guarda en settings.schema_version.
 * v1 = esquema base (schema.sql, versiones 1.0 y 1.1 de la plataforma).
 */
const SCHEMA_TARGET = 2;

function schema_migrations(): array
{
    return [
        2 => [
            'titulo' => 'v1.2 · Emisor de certificados, registro de gravámenes y portal de accionistas',
            'sql' => [
                "ALTER TABLE movements MODIFY cert_codigo VARCHAR(80) NULL",
                "ALTER TABLE movements ADD COLUMN res_pendiente TINYINT(1) NOT NULL DEFAULT 0 AFTER cert_doc_id",
                "CREATE TABLE IF NOT EXISTS gravamenes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    company_id INT NOT NULL,
                    member_id INT NOT NULL,
                    series_id INT NOT NULL,
                    tipo VARCHAR(40) NOT NULL,
                    a_favor_de VARCHAR(250) NOT NULL,
                    cantidad BIGINT NOT NULL,
                    fecha_anotacion DATE NOT NULL,
                    descripcion TEXT NULL,
                    doc_id INT NULL,
                    estado VARCHAR(10) NOT NULL DEFAULT 'VIGENTE',
                    alzado_fecha DATE NULL,
                    alzado_doc_id INT NULL,
                    alzado_obs TEXT NULL,
                    alzado_por INT NULL,
                    alzado_en DATETIME NULL,
                    creado_por INT NULL,
                    creado_en DATETIME NOT NULL,
                    INDEX (company_id), INDEX (member_id, series_id),
                    FOREIGN KEY (company_id) REFERENCES companies(id),
                    FOREIGN KEY (member_id) REFERENCES members(id),
                    FOREIGN KEY (series_id) REFERENCES series(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS certificates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    company_id INT NOT NULL,
                    member_id INT NOT NULL,
                    series_id INT NOT NULL,
                    anio INT NOT NULL,
                    correlativo INT NOT NULL,
                    numero VARCHAR(30) NOT NULL,
                    codigo VARCHAR(20) NOT NULL UNIQUE,
                    regimen VARCHAR(12) NOT NULL,
                    cantidad BIGINT NOT NULL,
                    votos_por_accion DECIMAL(10,4) NOT NULL,
                    estado_pago VARCHAR(12) NOT NULL,
                    monto_pagado BIGINT NULL,
                    monto_total BIGINT NULL,
                    plazo_entero DATE NULL,
                    res_pendiente TINYINT(1) NOT NULL DEFAULT 0,
                    datos TEXT NOT NULL,
                    lugar VARCHAR(120) NOT NULL,
                    fecha_emision DATE NOT NULL,
                    doc_id INT NULL,
                    sha256 CHAR(64) NULL,
                    estado VARCHAR(12) NOT NULL DEFAULT 'SIN_FIRMA',
                    firmado_doc_id INT NULL,
                    firmado_sha256 CHAR(64) NULL,
                    firmado_por INT NULL,
                    firmado_en DATETIME NULL,
                    baja_motivo TEXT NULL,
                    baja_en DATETIME NULL,
                    baja_por INT NULL,
                    creado_por INT NULL,
                    creado_en DATETIME NOT NULL,
                    UNIQUE KEY (company_id, anio, correlativo),
                    INDEX (member_id, series_id), INDEX (estado),
                    FOREIGN KEY (company_id) REFERENCES companies(id),
                    FOREIGN KEY (member_id) REFERENCES members(id),
                    FOREIGN KEY (series_id) REFERENCES series(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS portal_users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    person_id INT NOT NULL UNIQUE,
                    email VARCHAR(190) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    activo TINYINT(1) NOT NULL DEFAULT 1,
                    clave_inicial TINYINT(1) NOT NULL DEFAULT 1,
                    intentos_fallidos INT NOT NULL DEFAULT 0,
                    bloqueado_hasta DATETIME NULL,
                    ultimo_acceso DATETIME NULL,
                    creado_por INT NULL,
                    creado_en DATETIME NOT NULL,
                    FOREIGN KEY (person_id) REFERENCES persons(id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            ],
        ],
    ];
}

function schema_version(PDO $pdo): int
{
    try {
        $v = $pdo->query("SELECT v FROM settings WHERE k='schema_version'")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
    return $v === false ? 1 : (int)$v;
}

function schema_pending(PDO $pdo): array
{
    $cur = schema_version($pdo);
    return array_filter(schema_migrations(), fn($v) => $v > $cur, ARRAY_FILTER_USE_KEY);
}

/** Aplica las migraciones pendientes. Devuelve la lista de títulos aplicados. */
function schema_migrate(PDO $pdo): array
{
    $done = [];
    foreach (schema_pending($pdo) as $v => $m) {
        foreach ($m['sql'] as $sql) {
            try {
                $pdo->exec($sql);
            } catch (PDOException $e) {
                // Columna o índice ya existente (reintento de una migración interrumpida)
                if (!in_array((int)($e->errorInfo[1] ?? 0), [1060, 1061, 1050], true)) throw $e;
            }
        }
        $pdo->prepare("REPLACE INTO settings (k, v) VALUES ('schema_version', ?)")->execute([(string)$v]);
        $done[] = $m['titulo'];
    }
    return $done;
}
