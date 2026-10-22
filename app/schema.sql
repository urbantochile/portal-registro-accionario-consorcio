-- Plataforma de Accionistas CONSA — esquema MySQL 5.7+/8 / MariaDB 10.4+
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  nombre VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_master TINYINT(1) NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  clave_inicial TINYINT(1) NOT NULL DEFAULT 1,
  intentos_fallidos INT NOT NULL DEFAULT 0,
  bloqueado_hasta DATETIME NULL,
  ultimo_acceso DATETIME NULL,
  creado_en DATETIME NOT NULL,
  creado_por INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_permissions (
  user_id INT NOT NULL,
  modulo VARCHAR(40) NOT NULL,
  ver TINYINT(1) NOT NULL DEFAULT 0,
  editar TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, modulo),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  razon_social VARCHAR(250) NOT NULL,
  nombre_fantasia VARCHAR(190) NULL,
  rut VARCHAR(12) NOT NULL UNIQUE,
  tipo_social VARCHAR(20) NOT NULL,          -- SPA | SA_CERRADA | SA_ABIERTA
  regimen VARCHAR(12) NOT NULL,              -- RES | TRADICIONAL
  giro VARCHAR(250) NULL,
  fecha_constitucion DATE NULL,
  domicilio VARCHAR(250) NULL,
  comuna VARCHAR(120) NULL,
  ciudad VARCHAR(120) NULL,
  region VARCHAR(120) NULL,
  email VARCHAR(190) NULL,
  telefono VARCHAR(60) NULL,
  -- Inscripción sistema tradicional
  notaria VARCHAR(190) NULL,
  fecha_escritura DATE NULL,
  repertorio VARCHAR(60) NULL,
  cbr VARCHAR(190) NULL,
  fojas VARCHAR(30) NULL,
  numero_inscripcion VARCHAR(30) NULL,
  anio_inscripcion VARCHAR(10) NULL,
  fecha_diario_oficial DATE NULL,
  -- Inscripción RES (Ley 20.659)
  res_fecha_inscripcion DATE NULL,
  res_cve VARCHAR(40) NULL,
  res_numero_atencion VARCHAR(60) NULL,
  -- Capital
  capital_social BIGINT NULL,
  total_acciones_estatuto BIGINT NULL,
  -- Representante legal
  rep_nombre VARCHAR(190) NULL,
  rep_rut VARCHAR(12) NULL,
  rep_nacionalidad VARCHAR(80) NULL,
  rep_profesion VARCHAR(120) NULL,
  rep_cargo VARCHAR(120) NULL,
  rep_email VARCHAR(190) NULL,
  rep_telefono VARCHAR(60) NULL,
  rep_domicilio VARCHAR(250) NULL,
  poderes_fuente VARCHAR(20) NULL,           -- CERT_PODERES | ACTA_DIRECTORIO
  poderes_fecha DATE NULL,
  poderes_detalle VARCHAR(250) NULL,
  -- Imagen corporativa
  logo_path VARCHAR(250) NULL,
  color1_hex VARCHAR(7) NULL,
  color1_pantone VARCHAR(40) NULL,
  color2_hex VARCHAR(7) NULL,
  color2_pantone VARCHAR(40) NULL,
  -- Cumplimiento Ley 20.393 / 21.595
  mpd_adoptado TINYINT(1) NOT NULL DEFAULT 0,
  mpd_fecha_aprobacion DATE NULL,
  mpd_organo VARCHAR(190) NULL,
  enc_nombre VARCHAR(190) NULL,
  enc_rut VARCHAR(12) NULL,
  enc_email VARCHAR(190) NULL,
  enc_fecha_designacion DATE NULL,
  enc_vigencia_hasta DATE NULL,
  canal_denuncias VARCHAR(250) NULL,
  matriz_fecha DATE NULL,
  codigo_etica_fecha DATE NULL,
  cert_mpd TINYINT(1) NOT NULL DEFAULT 0,
  cert_mpd_entidad VARCHAR(190) NULL,
  cert_mpd_vigencia DATE NULL,
  capacitacion_fecha DATE NULL,
  cumplimiento_obs TEXT NULL,
  estado VARCHAR(12) NOT NULL DEFAULT 'ACTIVA',
  creado_en DATETIME NOT NULL,
  actualizado_en DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_companies (
  user_id INT NOT NULL,
  company_id INT NOT NULL,
  PRIMARY KEY (user_id, company_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  owner_type VARCHAR(20) NOT NULL,           -- company | person | movement | member
  owner_id INT NOT NULL,
  tipo VARCHAR(40) NOT NULL,
  nombre_original VARCHAR(250) NOT NULL,
  ruta VARCHAR(250) NOT NULL,
  mime VARCHAR(100) NOT NULL,
  bytes INT NOT NULL,
  sha256 CHAR(64) NOT NULL,
  subido_por INT NULL,
  subido_en DATETIME NOT NULL,
  vigente TINYINT(1) NOT NULL DEFAULT 1,
  INDEX (owner_type, owner_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS series (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  nombre VARCHAR(60) NOT NULL,
  votos_por_accion DECIMAL(10,4) NOT NULL DEFAULT 1,
  acciones_autorizadas BIGINT NULL,
  preferencias TEXT NULL,
  creado_en DATETIME NOT NULL,
  UNIQUE KEY (company_id, nombre),
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS persons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(10) NOT NULL,                 -- NATURAL | JURIDICA
  rut VARCHAR(12) NOT NULL UNIQUE,
  nombres VARCHAR(190) NULL,
  apellido_paterno VARCHAR(120) NULL,
  apellido_materno VARCHAR(120) NULL,
  razon_social VARCHAR(250) NULL,
  nacionalidad VARCHAR(80) NULL,
  fecha_nacimiento DATE NULL,
  estado_civil VARCHAR(40) NULL,
  profesion VARCHAR(190) NULL,
  calle VARCHAR(190) NULL,
  numero VARCHAR(30) NULL,
  depto VARCHAR(30) NULL,
  comuna VARCHAR(120) NULL,
  ciudad VARCHAR(120) NULL,
  region VARCHAR(120) NULL,
  pais VARCHAR(80) NULL,
  email VARCHAR(190) NULL,
  telefono VARCHAR(60) NULL,
  jur_tipo_sociedad VARCHAR(80) NULL,
  jur_fecha_constitucion DATE NULL,
  jur_rep_nombre VARCHAR(190) NULL,
  jur_rep_rut VARCHAR(12) NULL,
  -- Debida diligencia
  dd_estado VARCHAR(12) NOT NULL DEFAULT 'PENDIENTE',   -- PENDIENTE | APROBADA | RECHAZADA
  dd_pep VARCHAR(4) NULL,                    -- SI | NO
  dd_pep_detalle VARCHAR(250) NULL,
  dd_listas_resultado VARCHAR(20) NULL,      -- SIN_COINCIDENCIAS | CON_COINCIDENCIAS
  dd_listas_fecha DATE NULL,
  dd_origen_fondos TEXT NULL,
  dd_checks TEXT NULL,                       -- JSON de casillas verificadas
  dd_revisor INT NULL,
  dd_fecha DATETIME NULL,
  dd_obs TEXT NULL,
  creado_en DATETIME NOT NULL,
  creado_por INT NULL,
  actualizado_en DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS beneficiarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  person_id INT NOT NULL,
  nombre VARCHAR(190) NOT NULL,
  rut VARCHAR(20) NULL,
  porcentaje DECIMAL(6,2) NOT NULL,
  es_pep TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (person_id) REFERENCES persons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  person_id INT NOT NULL,
  numero INT NOT NULL,
  fecha_ingreso DATE NOT NULL,
  observaciones TEXT NULL,
  creado_en DATETIME NOT NULL,
  creado_por INT NULL,
  UNIQUE KEY (company_id, person_id),
  UNIQUE KEY (company_id, numero),
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (person_id) REFERENCES persons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  fecha DATE NOT NULL,
  texto TEXT NOT NULL,
  automatica TINYINT(1) NOT NULL DEFAULT 0,
  movement_id INT NULL,
  creado_por INT NULL,
  creado_en DATETIME NOT NULL,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  series_id INT NOT NULL,
  numero INT NOT NULL,
  fecha DATE NOT NULL,
  tipo VARCHAR(20) NOT NULL,                 -- EMISION | TRANSFERENCIA
  vendedor_member_id INT NULL,               -- NULL = la sociedad (emisión)
  comprador_member_id INT NOT NULL,
  cantidad BIGINT NOT NULL,
  precio_total BIGINT NULL,
  titulo_inutilizado VARCHAR(60) NULL,
  titulo_nuevo_comprador VARCHAR(60) NULL,
  titulo_nuevo_vendedor VARCHAR(60) NULL,
  cert_codigo VARCHAR(80) NOT NULL,
  cert_doc_id INT NULL,
  cert_vendedor_codigo VARCHAR(80) NULL,
  cert_vendedor_doc_id INT NULL,
  observaciones TEXT NULL,
  anulado TINYINT(1) NOT NULL DEFAULT 0,
  anulado_motivo TEXT NULL,
  anulado_por INT NULL,
  anulado_en DATETIME NULL,
  creado_por INT NULL,
  creado_en DATETIME NOT NULL,
  UNIQUE KEY (company_id, numero),
  UNIQUE KEY (cert_codigo),
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (series_id) REFERENCES series(id),
  FOREIGN KEY (comprador_member_id) REFERENCES members(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS libro_entregas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  folio VARCHAR(60) NOT NULL UNIQUE,
  entregado_a_nombre VARCHAR(190) NOT NULL,
  entregado_a_rut VARCHAR(12) NOT NULL,
  generado_por INT NOT NULL,
  generado_en DATETIME NOT NULL,
  sha256 CHAR(64) NULL,
  FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  user_email VARCHAR(190) NULL,
  accion VARCHAR(60) NOT NULL,
  entidad VARCHAR(40) NULL,
  entidad_id INT NULL,
  company_id INT NULL,
  detalle TEXT NULL,
  ip VARCHAR(64) NULL,
  user_agent VARCHAR(250) NULL,
  creado_en DATETIME NOT NULL,
  INDEX (creado_en), INDEX (user_id), INDEX (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS backups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(12) NOT NULL,                 -- DESCARGA | RESTAURACION
  user_id INT NULL,
  archivo VARCHAR(250) NULL,
  bytes BIGINT NULL,
  con_documentos TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS holidays (
  fecha DATE PRIMARY KEY,
  nombre VARCHAR(190) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(60) PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
