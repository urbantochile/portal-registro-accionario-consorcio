<?php
/**
 * Contenido del manual de usuario. Fuente única para la ayuda en pantalla y para los manuales PDF.
 * 'master' => true: capítulo exclusivo del Máster. 'perm': módulo requerido para verlo.
 * 'solo_master': párrafos adicionales visibles solo para el Máster.
 */
return [
    'inicio' => [
        'titulo' => 'Primeros pasos',
        'perm' => null,
        'intro' => 'La Plataforma de Accionistas CONSA lleva, para cada sociedad del grupo, un Registro de Socios y un Registro de Accionistas. Es multisociedad: una misma persona puede ser socia y accionista de varias sociedades.',
        'pasos' => [
            ['Cómo ingresar', ['Abra https://socialis.grupoconsa.cl/ en su navegador.', 'Escriba su correo y su clave, y presione «Ingresar».', 'Tras 5 intentos fallidos el usuario se bloquea 15 minutos.', 'Por seguridad la sesión se cierra sola después de 30 minutos sin actividad.']],
            ['El orden de trabajo', ['1. Se registra la sociedad (con su régimen RES o tradicional y sus documentos).', '2. Se registra a la persona y se aplica la debida diligencia.', '3. Aprobada la diligencia, la persona se incorpora al Registro de Socios de la sociedad.', '4. Recién entonces puede recibir acciones en el Registro de Accionistas (emisión o compra).']],
            ['El botón «? Manual»', ['Cada pantalla tiene un botón «? Manual» arriba a la derecha que abre la explicación de ese módulo y sus preguntas frecuentes.']],
        ],
        'solo_master' => ['Usted es el usuario Máster: ve todas las sociedades y todos los módulos, crea usuarios, administra respaldos y feriados, y es el único que puede anular movimientos o reabrir una diligencia aprobada.'],
        'faq' => [
            ['Olvidé mi clave, ¿qué hago?', 'Pida al usuario Máster que le asigne una clave nueva desde «Usuarios y permisos». Al ingresar, cámbiela en «Cambiar clave».'],
            ['¿Por qué no veo algunos módulos o sociedades?', 'El Máster define qué módulos y qué sociedades ve cada usuario. Si necesita acceso adicional, solicítelo al Máster.'],
            ['¿Queda registro de lo que hago?', 'Sí. Toda acción (ingresos, cambios, descargas, consultas de documentos) queda en la bitácora con fecha, hora, usuario e IP.'],
        ],
    ],
    'panel' => [
        'titulo' => 'Panel de control',
        'perm' => null,
        'intro' => 'Es la pantalla inicial. Resume las sociedades a las que usted tiene acceso y muestra las alertas pendientes.',
        'pasos' => [
            ['Qué muestra', ['Número de sociedades (RES y tradicionales), socios registrados, accionistas vigentes y diligencias pendientes.', 'Alertas: sociedades con documentos faltantes, cumplimiento Ley 20.393 / 21.595 pendiente y personas con diligencia sin resolver. Haga clic en una alerta para ir a resolverla.', 'Tabla de sociedades con su régimen, número de socios, accionistas y acciones.']],
        ],
        'solo_master' => ['Además verá el estado del respaldo diario. Si es día hábil y no se ha descargado el respaldo, aparece un aviso amarillo en todas las pantallas hasta que lo descargue.'],
        'faq' => [
            ['¿Por qué una sociedad aparece «Pendientes»?', 'Porque le falta algún documento de respaldo o algún antecedente de cumplimiento normativo. Entre a la sociedad para ver exactamente qué falta.'],
        ],
    ],
    'sociedades' => [
        'titulo' => 'Sociedades',
        'perm' => 'sociedades',
        'intro' => 'Aquí se registra cada sociedad. Al registrarla se abren automáticamente su Registro de Socios y su Registro de Accionistas. Solo se registran sociedades por acciones: SpA y S.A. (cerrada o abierta).',
        'pasos' => [
            ['Registrar una sociedad', ['Presione «+ Nueva sociedad».', 'Paso 1 · Identificación: razón social, RUT (se valida el dígito verificador), tipo social, fecha de constitución y domicilio.', 'Paso 2 · Régimen: elija RES (Registro de Empresas y Sociedades, Ley 20.659) o Tradicional (escritura inscrita en el Conservador). Según lo elegido se piden los datos de inscripción: en RES la fecha de inscripción y el CVE; en tradicional notaría, fecha de escritura, fojas, número, año, Conservador y publicación en el Diario Oficial.', 'Paso 3 · Capital y series de acciones: defina una o varias series con el nombre del estatuto («Única», «Serie A», «Serie B», «C», «D», «Preferentes», etc.), cada una con votos por acción, acciones autorizadas y preferencias. Use «+ Agregar serie» para sumar filas.', 'Paso 4 · Representante legal y el documento donde constan sus poderes: certificado de poderes o, si no existe, la última sesión de directorio que otorga los poderes.', 'Paso 5 · Adjunte en PDF el estatuto social y el certificado de poderes (o el acta de directorio). Son obligatorios.', 'Paso 6 · Imagen corporativa: logotipo (PNG o JPG hasta 2 MB) y colores primario y secundario en HEX y Pantone. Se usan en el PDF del Libro de Accionistas.', 'Paso 7 · Cumplimiento Ley 20.393 y Ley 21.595: Modelo de Prevención de Delitos, Encargado de Prevención, canal de denuncias, matriz de riesgos, código de ética, capacitación y sus documentos.', 'Presione «Registrar sociedad».']],
            ['Series de acciones', ['Una sociedad puede tener todas las series que necesite (A, B, C, D o con otro nombre).', 'En la ficha de la sociedad, sección «Series de acciones», agregue nuevas series o edite nombre, votos por acción, acciones autorizadas y preferencias.', 'Una serie sin movimientos puede eliminarse; con movimientos, no.', 'Si fija acciones autorizadas, el sistema no permite emitir más que ese número.', 'En el registro de accionistas los saldos, % del capital y % de votos se muestran por cada serie.']],
            ['Documentos', ['En la ficha, «Agregar documento» permite subir nuevos PDF (por ejemplo un estatuto modificado o un nuevo certificado de poderes).', '«Dar de baja» marca un documento como no vigente; no se borra y sigue en el historial y en los respaldos.']],
        ],
        'solo_master' => ['El Máster puede desactivar una sociedad: queda visible, pero no admite nuevos socios ni movimientos.'],
        'faq' => [
            ['¿Qué diferencia hay entre RES y tradicional en la plataforma?', 'En RES el registro de accionistas es de administración interna: no acredita nada, solo lo ven usuarios autorizados y se descarga en Excel; el código de los certificados es el CVE de 12 caracteres. En el sistema tradicional el registro es un documento válido de control de accionistas y se emite el PDF «Libro de Accionistas» para el representante legal.'],
            ['¿Cuántas series puede tener una sociedad?', 'Las que indique su estatuto, sin límite. Los nombres son libres (A, B, C, D, Única, Preferentes…) pero no pueden repetirse dentro de la misma sociedad. Una serie con votos por acción 0 se considera sin derecho a voto para el cálculo del % de votos.'],
            ['¿Puedo registrar una sociedad sin el estatuto?', 'No. El estatuto y el certificado de poderes (o el acta de directorio) son obligatorios para crearla.'],
            ['¿Qué pasa si falta algo del cumplimiento Ley 20.393 / 21.595?', 'La sociedad funciona normalmente, pero queda marcada «cumplimiento pendiente» con una alerta en el panel hasta que se complete.'],
            ['¿Puedo cambiar el régimen después?', 'Sí, editando la sociedad; el cambio queda en la bitácora. Hágalo solo si el régimen fue mal ingresado o la sociedad migró de régimen.'],
        ],
    ],
    'personas' => [
        'titulo' => 'Personas y debida diligencia',
        'perm' => 'socios',
        'intro' => 'Toda persona natural o jurídica se registra una sola vez y se le aplica la debida diligencia. Solo con la diligencia aprobada puede incorporarse al Registro de Socios de una o varias sociedades.',
        'pasos' => [
            ['Registrar una persona', ['Presione «+ Nueva persona» y elija natural o jurídica.', 'Ingrese el RUT: el sistema valida el dígito verificador y avisa si la persona ya existe.', 'Natural: nombres, apellidos, nacionalidad, fecha de nacimiento, estado civil y profesión. No pueden registrarse menores de edad: la persona debe tener 18 años cumplidos. Jurídica: razón social, tipo de sociedad, giro y representante legal con su RUT.', 'Domicilio completo, correo y teléfono son obligatorios.']],
            ['Aplicar la debida diligencia', ['En la ficha de la persona, sección «Debida diligencia», marque cada verificación y adjunte su respaldo (PDF o imagen): identidad, declaración PEP, revisión en listas ONU/OFAC, beneficiarios finales (solo jurídicas), origen de fondos y declaración Ley 20.393 / 21.595.', 'Indique si es PEP, el resultado y la fecha de la revisión en listas, y el origen de los fondos.', 'En personas jurídicas registre los beneficiarios finales (10 % o más de participación o control).', '«Guardar avance» guarda sin aprobar. «Aprobar diligencia» valida que todo esté completo. «Rechazar» exige indicar el motivo.']],
            ['Incorporar a una o más sociedades', ['Con la diligencia aprobada, en la misma ficha use «Incorporar al registro de socios de otra sociedad»: elija la sociedad, la fecha de ingreso y, si tiene, la firma escaneada.', 'Repita para cada sociedad en que participe.']],
        ],
        'solo_master' => ['Una diligencia aprobada queda bloqueada. Solo el Máster puede «Reabrir diligencia»; mientras esté reabierta, la persona no puede recibir nuevas acciones.'],
        'faq' => [
            ['¿Por qué no puedo aprobar la diligencia?', 'El sistema muestra exactamente lo que falta: casillas sin marcar, documentos sin subir, PEP sin indicar, revisión en listas sin fecha, origen de fondos vacío o beneficiarios finales no registrados.'],
            ['La revisión en listas dio coincidencias, ¿qué hago?', 'No es posible aprobar. Registre el caso en observaciones y escale al Máster y al Encargado de Prevención de la sociedad.'],
            ['¿La plataforma consulta automáticamente las listas ONU/OFAC?', 'No. La revisión se hace en los sitios oficiales y se sube la evidencia (captura o reporte) como respaldo.'],
            ['¿Puedo registrar a un menor de edad?', 'No. La plataforma no permite registrar personas naturales menores de 18 años, ni siquiera representadas.'],
            ['¿Puedo corregir el RUT?', 'No. El RUT no se modifica; si está errado, registre a la persona nuevamente con el RUT correcto.'],
        ],
    ],
    'socios' => [
        'titulo' => 'Registro de socios',
        'perm' => 'socios',
        'intro' => 'Replica el libro «Registro de Socios» de cada sociedad: N°, nombres, edad, estado civil, fecha de ingreso, profesión, domicilio, carnet (RUT), firma y observaciones.',
        'pasos' => [
            ['Incorporar un socio', ['Elija la sociedad en el selector superior.', 'Abra «+ Incorporar socio», elija la persona (solo aparecen las que tienen diligencia aprobada), la fecha de ingreso y, opcionalmente, la firma.', 'El número de socio se asigna solo, en orden correlativo (01, 02, 03…).']],
            ['Anotaciones y firmas', ['En la columna «Observaciones», «+ anotación / firma» permite agregar una anotación con fecha o adjuntar la firma escaneada del socio.', 'Cuando un accionista vende todas sus acciones, el sistema agrega automáticamente la anotación «Enajenó la totalidad de sus acciones de la sociedad …» con la fecha y el número de transferencia.']],
            ['Descargar Excel', ['«Descargar Excel» entrega el registro de socios completo de la sociedad elegida, con todos sus datos, anotaciones y acciones vigentes.']],
        ],
        'faq' => [
            ['¿Puedo borrar un socio?', 'No. El registro de socios es histórico. Si alguien deja de ser accionista, queda la anotación correspondiente.'],
            ['Una persona es socia de dos sociedades, ¿se registra dos veces?', 'La persona se registra una sola vez; luego se incorpora al registro de socios de cada sociedad.'],
        ],
    ],
    'accionistas' => [
        'titulo' => 'Registro de accionistas',
        'perm' => 'accionistas',
        'intro' => 'Replica el libro «Registro de Accionistas»: cada compra y venta se anota y los saldos se calculan automáticamente. Solo pueden recibir acciones quienes estén en el Registro de Socios de la sociedad.',
        'pasos' => [
            ['Registrar una emisión (suscripción)', ['Presione «+ Registrar movimiento» y elija «Emisión / suscripción».', 'Elija la serie, la fecha, la cantidad y el socio comprador.', 'Ingrese los números de título si corresponde y el precio (opcional).', 'Ingrese el código del certificado entregado por la sociedad y suba su PDF (obligatorio). En sociedades RES el código es el CVE de 12 caracteres.', 'En sociedades RES, si el comprador aún no inscribe su compra en el portal del RES, marque «Registro RES pendiente»: el movimiento se registra sin CVE ni PDF y queda una alerta en el panel.']],
            ['Completar el CVE pendiente', ['En la pestaña «Movimientos», la compra aparece con «RES pendiente». Presione «Registrar CVE», ingrese el CVE de 12 caracteres y suba el certificado del RES en PDF.', 'El certificado interno vigente del accionista en esa serie se reemplaza para emitir uno nuevo con el CVE.']],
            ['Registrar una transferencia (compraventa)', ['Elija «Transferencia», la serie, la fecha y la cantidad.', 'Elija el vendedor (solo aparecen accionistas con saldo) y el comprador (desde el registro de socios).', 'Complete los títulos: inutilizado, nuevo del comprador y nuevo del vendedor.', 'Código y PDF del certificado del comprador: obligatorios. Código y PDF del certificado del vendedor: opcionales (si le queda saldo).', 'El sistema impide vender más acciones de las que el vendedor tiene, también considerando fechas anteriores.']],
            ['Consultar saldos', ['Pestaña «Saldos por accionista»: accionistas vigentes con acciones por serie, total, % del capital y % de votos.', '«Hoja» abre la hoja del accionista con el mismo formato del libro físico: fecha, N° de transferencia, título inutilizado, comprado a, vendido a, títulos nuevos, compras, ventas, saldo, observaciones y título emitido.', 'Pestaña «Movimientos»: todas las anotaciones en orden cronológico, con enlace a los certificados.']],
            ['Venta total', ['Si un accionista vende todas sus acciones, sale automáticamente de la lista de accionistas vigentes y en el registro de socios queda la anotación de que enajenó la totalidad.']],
            ['Descargas', ['Sociedades RES: «Descargar Excel» con saldos, movimientos y hojas por accionista.', 'Sociedades tradicionales: «Libro de Accionistas (PDF)», solo para usuarios autorizados (ver capítulo Libro de Accionistas).']],
        ],
        'solo_master' => ['Solo el Máster puede anular un movimiento («Anular», con motivo obligatorio). No se borra: queda tachado y excluido de los saldos. Si anularlo dejaría a alguien con saldo negativo, primero debe anular los movimientos posteriores que dependen de él.'],
        'faq' => [
            ['¿Por qué el comprador no aparece en la lista?', 'Porque no está en el registro de socios de esa sociedad o su diligencia no está aprobada.'],
            ['¿La plataforma emite los certificados?', 'No. Los certificados los entrega la sociedad (o el Registro de Empresas y Sociedades en las RES) y aquí solo se registran su código y su PDF.'],
            ['El sistema dice que el código ya fue registrado', 'Cada código de certificado se puede usar una sola vez. Verifique que no esté registrando dos veces el mismo movimiento.'],
            ['Me equivoqué en un movimiento', 'Pida al Máster que lo anule indicando el motivo, y regístrelo nuevamente de forma correcta.'],
        ],
    ],
    'libro' => [
        'titulo' => 'Libro de Accionistas (PDF)',
        'perm' => 'libro_pdf',
        'intro' => 'Solo para sociedades del sistema registral tradicional. Genera el PDF «LIBRO DE ACCIONISTAS» con el logotipo y los colores corporativos de la sociedad, para entregarlo únicamente al representante legal.',
        'pasos' => [
            ['Generar el libro', ['En «Registro de accionistas» elija la sociedad tradicional y presione «Libro de Accionistas (PDF)».', 'Revise el nombre y RUT del representante legal registrado en la sociedad.', 'Marque la confirmación de entrega y presione «Generar y descargar PDF».']],
            ['Contenido del PDF', ['Portada con logotipo, datos de constitución e inscripción, capital, representante legal y la constancia de entrega con espacio para la firma de recepción.', 'Resumen de accionistas vigentes y de series.', 'Una hoja por cada accionista con el formato del libro físico.', 'En cada página: folio, «Entregado a: nombre, RUT – fecha» y número de página.']],
        ],
        'faq' => [
            ['¿Puedo entregar el libro a otra persona?', 'No. El libro se emite siempre a nombre del representante legal registrado. Si cambió el representante, primero actualice la sociedad con el nuevo representante y su certificado de poderes.'],
            ['¿Queda registro de cada entrega?', 'Sí: folio correlativo por sociedad y año, fecha, usuario que lo generó y huella SHA-256 del PDF, en la bitácora.'],
        ],
    ],
    'certificados' => [
        'titulo' => 'Emisor de certificados de accionistas',
        'perm' => 'certificados',
        'intro' => 'Genera el certificado de accionistas de cada sociedad con el modelo del grupo, su logotipo y colores corporativos. El certificado solo es válido cuando lo firma electrónicamente en FirmaYa el representante legal registrado y se sube firmado a la plataforma.',
        'pasos' => [
            ['Qué muestra la pantalla', ['Elija la sociedad. La pestaña «Accionistas y series» lista cada accionista con acciones por serie (un certificado por serie), sus gravámenes y el estado de su certificado: «Por emitir», «Pendiente de firma» o «Firmado · vigente».', 'La pestaña «Certificados emitidos» muestra todos, con su estado: pendiente de firma, firmado, reemplazado o anulado.']],
            ['Paso 1 · Emitir', ['Presione «Emitir» (o «Reemitir») en la fila del accionista y serie.', 'Revise los datos que tomará el certificado: titular, serie, votos, cantidad (con su expresión en letras), gravámenes, folio del certificado de venta (tradicionales) o CVE del RES, inscripción y firmante.', 'Complete el estado de pago: íntegramente pagadas; parcialmente pagadas (monto pagado, total y, si corresponde, plazo de entero); o no pagadas (con plazo de entero).', 'En sociedades RES, si el accionista aún no inscribe su compra en el portal del RES, marque «Registro RES pendiente» (se marca solo si la compra se registró así).', 'Indique lugar y fecha de emisión y presione «Generar certificado». Se asigna el N° AÑO-correlativo por sociedad (ej.: 2026-000001) y un código de verificación único.']],
            ['Paso 2 · Firmar en FirmaYa', ['En la ficha del certificado, «Descargar para FirmaYa» baja el PDF sin firma (requiere el permiso «Ver certificados sin firma»).', 'El gerente general o representante legal indicado en el certificado lo firma en la plataforma FirmaYa.', 'Descargue desde FirmaYa el PDF ya firmado.']],
            ['Paso 3 · Subir el firmado', ['En la ficha del certificado, «Subir PDF firmado».', 'La plataforma verifica que el PDF traiga una firma electrónica incrustada y que corresponda a ese certificado (N° y código). Si no, lo rechaza.', 'Con el firmado cargado, el certificado queda «Firmado · vigente» y disponible para el accionista en su portal.']],
            ['Contenido del certificado', ['Tradicionales: título «CERTIFICADO DE ACCIONISTAS», constancia de gravámenes (libres o con gravamen: naturaleza, a favor de y fecha) y folio del certificado de venta, validable en https://socialis.grupoconsa.cl/.', 'RES: el mismo título con la mención «Documento de control interno — Régimen RES», el CVE (o «Registro RES pendiente») validable en https://www.registrodeempresasysociedades.cl/VerificarCertificados.aspx, la leyenda de que no es posible certificar gravámenes y que deben verificarse en el portal del Registro de Empresas y Sociedades, y la advertencia de que el certificado es únicamente para control interno y no reemplaza el emitido por dicho portal.', 'Ambos: leyenda de que solo es válido con la firma electrónica del representante legal y enlace de validación de la firma en https://firmaya.idok.cl/doc_validator.']],
            ['Vigencia y reemplazo automático', ['Cuando cambia la situación del accionista en una serie (compra, venta, anulación de un movimiento, anotación o alzamiento de un gravamen, o registro del CVE), su certificado vigente pasa a «Reemplazado», deja de verse en el portal y aparece como «Por emitir».', 'Emitir un nuevo certificado de la misma serie también reemplaza al anterior. Los reemplazados se conservan en el historial.']],
            ['Acceso del accionista al portal', ['En «Personas y diligencia», ficha de la persona, sección «Acceso al portal de accionista»: ingrese su correo y una clave inicial y presione «Crear acceso al portal».', 'Comunique la clave por un canal seguro (la plataforma no envía correos). Al primer ingreso el accionista debe cambiarla.', 'El accionista entra por la misma dirección de la plataforma y solo ve sus certificados firmados y vigentes de todas las sociedades en que participa.', 'Desde la misma sección se asigna una nueva clave inicial o se desactiva el acceso.']],
        ],
        'solo_master' => ['El Máster decide quién puede ver certificados sin firma (permiso «Ver certificados sin firma») y es el único que puede anular un certificado, con motivo.'],
        'faq' => [
            ['¿Por qué no puedo descargar el PDF para firmar?', 'Porque su usuario no tiene el permiso «Ver certificados sin firma». Pídalo al Máster o solicite que otra persona autorizada lo descargue.'],
            ['La plataforma rechazó el PDF firmado', 'Suba el archivo que entrega FirmaYa después de firmar (no el original) y verifique que sea del mismo certificado. Si firmó una versión anterior, emita y firme la vigente.'],
            ['El accionista tiene acciones en dos series', 'Se emite un certificado por cada serie.'],
            ['¿Qué pasa si el representante legal cambia?', 'Actualice la ficha de la sociedad con el nuevo representante y su documento de poderes y reemita los certificados que deban quedar firmados por él.'],
            ['El accionista no ve su certificado en el portal', 'Solo se muestran certificados firmados y vigentes. Revise que se haya subido el PDF firmado y que no haya quedado reemplazado por un cambio posterior.'],
        ],
    ],
    'gravamenes' => [
        'titulo' => 'Registro de gravámenes',
        'perm' => 'gravamenes',
        'intro' => 'Anota y alza los gravámenes que afectan las acciones de los accionistas (prenda, embargo, prohibición, usufructo, medida precautoria u otro). En sociedades tradicionales se reflejan en el certificado; en sociedades RES se anotan solo para control interno.',
        'pasos' => [
            ['Anotar un gravamen', ['Elija la sociedad y abra «+ Anotar gravamen».', 'Seleccione el accionista, la serie y la cantidad de acciones afectadas (no puede superar su saldo).', 'Indique el tipo, a favor de quién, la fecha de anotación y una descripción (rol de causa, escritura, monto, plazo).', 'Adjunte el documento de respaldo en PDF (obligatorio) y presione «Anotar gravamen».', 'Queda una anotación automática en el registro de socios y el certificado vigente del accionista en esa serie pasa a «Reemplazado» para emitir uno nuevo con el gravamen.']],
            ['Alzar un gravamen', ['En la fila del gravamen vigente, «Alzar»: indique la fecha, una observación y adjunte el documento de alzamiento (PDF).', 'El gravamen queda «Alzado», se anota en el registro de socios y el certificado vigente se reemplaza para emitir uno nuevo «libre de gravámenes».']],
            ['Ventas de acciones gravadas', ['Al registrar una transferencia, si el vendedor tiene gravámenes vigentes en esa serie, el formulario muestra una advertencia y, al guardar, un aviso. La venta no se bloquea: revise el caso antes de continuar.']],
        ],
        'faq' => [
            ['¿Se certifican los gravámenes de una sociedad RES?', 'No. El certificado RES indica que no es posible certificar su existencia o inexistencia y que deben verificarse en el portal del Registro de Empresas y Sociedades. La anotación aquí es solo de control interno.'],
            ['¿Puedo borrar un gravamen mal ingresado?', 'No se borran: regístrelo como alzado, indicando en la observación que fue un error, y anote el correcto.'],
        ],
    ],
    'actualizacion' => [
        'titulo' => 'Actualización de la plataforma',
        'perm' => 'respaldos',
        'master' => true,
        'intro' => 'Cuando se instalan archivos de una versión nueva que trae cambios en la base de datos, la plataforma lo detecta y lleva al Máster a la pantalla «Actualización de la base de datos».',
        'pasos' => [
            ['Qué hacer', ['Descargue un respaldo completo (el enlace está en la misma pantalla).', 'Marque «Ya descargué un respaldo completo de hoy» y presione «Actualizar base de datos».', 'Mientras no se complete, los demás usuarios ven «Plataforma en actualización».', 'La actualización solo agrega tablas y columnas; no borra datos. Queda registrada en la bitácora.']],
        ],
        'faq' => [['¿Qué hago si la actualización falla?', 'No siga usando la plataforma. Restaure el respaldo descargado y contacte a soporte con el mensaje de error.']],
    ],
    'bitacora' => [
        'titulo' => 'Bitácora de actividad',
        'perm' => 'bitacora',
        'intro' => 'Registro inalterable de todas las actuaciones de los usuarios.',
        'pasos' => [
            ['Consultar', ['Filtre por usuario, tipo de acción, rango de fechas o texto del detalle.', '«Descargar Excel» exporta el resultado filtrado.']],
        ],
        'faq' => [
            ['¿Se puede borrar un registro de la bitácora?', 'No. La bitácora no se puede editar ni borrar desde la plataforma.'],
        ],
    ],
    'usuarios' => [
        'titulo' => 'Usuarios y permisos',
        'perm' => 'usuarios',
        'master' => true,
        'intro' => 'Exclusivo del Máster. Permite crear usuarios y darles acceso por módulo y por sociedad.',
        'pasos' => [
            ['Crear un usuario', ['Presione «+ Nuevo usuario», ingrese nombre, correo y una clave inicial (mínimo 10 caracteres con letras y números).', 'Marque los módulos: «Ver» o «Ver y editar».', 'Marque las sociedades a las que tendrá acceso.', 'Permisos nuevos (v1.2): «Certificados» (ver; con editar: emitir, subir firmados y crear accesos al portal de accionistas), «Ver certificados sin firma» y «Registro de gravámenes» (ver; con editar: anotar y alzar).', 'Comunique la clave inicial por un canal seguro; el usuario verá un aviso para cambiarla.']],
            ['Modificar o desactivar', ['Con «Editar» cambie permisos, sociedades o asigne una nueva clave.', 'Desmarque «Usuario activo» para impedir su ingreso sin borrar su historial.']],
        ],
        'faq' => [
            ['¿Puedo crear otro Máster?', 'No. Existe un solo usuario Máster; los demás usuarios tienen los permisos que usted les asigne.'],
            ['Un usuario quedó bloqueado', 'Se desbloquea solo a los 15 minutos, o asígnele una nueva clave desde «Editar».'],
        ],
    ],
    'respaldos' => [
        'titulo' => 'Respaldos y feriados',
        'perm' => 'respaldos',
        'master' => true,
        'intro' => 'Exclusivo del Máster. Debe descargarse un respaldo todos los días hábiles (no sábados, domingos ni feriados).',
        'pasos' => [
            ['Descargar el respaldo diario', ['Presione «Descargar respaldo JSON». Con «Incluir documentos» marcado, el archivo contiene además todos los PDF e imágenes.', 'Guárdelo fuera del servidor. Mientras no lo haga, en días hábiles verá el aviso amarillo.']],
            ['Restaurar', ['Seleccione el archivo .json, escriba RESTAURAR y confirme.', 'Se reemplazan TODOS los datos (incluidos usuarios y claves). Antes, el sistema guarda una copia del estado actual en la carpeta storage/backups del servidor.', 'Al terminar se cierra la sesión: ingrese con las credenciales vigentes en el respaldo.']],
            ['Feriados', ['Vienen precargados los feriados legales de Chile 2026–2030.', 'Agregue feriados extraordinarios (elecciones, leyes especiales) o quite los que no correspondan. Para años siguientes use «Cargar feriados legales».']],
        ],
        'faq' => [
            ['El respaldo es muy grande para restaurar', 'Aumente upload_max_filesize y post_max_size en SiteGround (Site Tools › Devs › PHP Manager › PHP Variables) o restaure un respaldo sin documentos y copie la carpeta storage/docs por el Administrador de archivos.'],
            ['¿El respaldo contiene datos sensibles?', 'Sí: datos personales, diligencias y documentos. Resguárdelo como información confidencial.'],
        ],
    ],
    'cuenta' => [
        'titulo' => 'Cambio de clave',
        'perm' => null,
        'intro' => 'Cada usuario puede cambiar su clave en «Cambiar clave» (arriba a la derecha).',
        'pasos' => [
            ['Cambiar la clave', ['Ingrese su clave actual, la nueva clave y repítala.', 'La clave debe tener al menos 10 caracteres y combinar letras y números.']],
        ],
        'solo_master' => ['El Máster ingresa inicialmente con master@consa.cl y la clave entregada en la instalación. Cámbiela en el primer ingreso.'],
        'faq' => [['¿Cada cuánto debo cambiar la clave?', 'Se recomienda cambiarla al menos cada 90 días y siempre que sospeche que alguien la conoce.']],
    ],
];
