# Plan del sistema autoadministrable

## Estado de ejecución

| Hito | Estado |
| --- | --- |
| A — Acceso, seguridad, roles y dashboard base | Implementado |
| B — Multimedia y noticias con Tiptap | Implementado |
| C — Categorías, hero y sliders | Implementado |
| D — Programas, horarios y streaming | Pendiente |
| E — Publicidad, sidebars y apariencia | Pendiente |
| F — Estadísticas, auditoría, SEO y mantenimiento | Pendiente |
| G — PWA y despliegue | Pendiente |

## 1. Objetivo

Convertir el portal público actual en una plataforma administrable desde
Laravel, conservando el diseño, las rutas con slugs, los módulos editoriales y
el reproductor ya construidos.

El administrador no deberá editar código, `.env`, seeders ni directamente la
base de datos para realizar las tareas habituales del portal.

## 2. Alcance de la primera versión administrativa

La primera versión permitirá:

- iniciar sesión de manera segura;
- administrar usuarios y roles;
- crear, revisar, programar, publicar y archivar noticias;
- ordenar categorías y administrar etiquetas;
- subir y reutilizar imágenes;
- administrar programas y programación semanal;
- configurar audio y video streaming;
- decidir las noticias y el comportamiento del hero;
- configurar sliders, sidebars, redes sociales y datos institucionales;
- administrar publicidad y sus periodos de vigencia;
- editar SEO básico;
- editar noticias con Tiptap Rich Text Editor;
- consultar un dashboard estadístico con indicadores y gráficos;
- consultar actividad y contenido pendiente.

## 3. Principios

1. El portal público continuará funcionando durante la construcción del panel.
2. El panel usará los modelos actuales; no se duplicará el contenido.
3. Las acciones sensibles se protegerán con autenticación, autorización,
   validación y protección CSRF.
4. Las imágenes se guardarán mediante la biblioteca multimedia, no en campos de
   texto introducidos manualmente.
5. Los cambios de apariencia se almacenarán en `portal_settings`.
6. Los streams se consumirán desde el proveedor, sin retransmitirlos desde el
   hosting compartido.
7. Las tareas programadas deberán funcionar con un único cron de Laravel.
8. Cada fase terminará con pruebas antes de comenzar la siguiente.
9. Todo el sistema utilizará UTF-8 sin BOM; MySQL/MariaDB usará `utf8mb4` y
   ninguna importación se almacenará sin validar y normalizar su codificación.

## 4. Roles y permisos

El rol **Superadministrador** es el “Maestro” del sistema: tiene acceso
irrestricto a todos los módulos y puede crear otro superadministrador,
administradores, editores y locutores.

| Acción | Superadministrador | Administrador | Editor | Locutor |
| --- | --- | --- | --- | --- |
| Acceder al dashboard | Todo | Sí | Sí | Sí |
| Administrar módulos y permisos | Todo | Solo los delegados | No | No |
| Crear superadministradores | Sí | No | No | No |
| Crear administradores | Sí | No | No | No |
| Crear editores y locutores | Sí | Sí | No | No |
| Configuración general y streaming | Todo | Según permiso | No | Según permiso |
| Publicidad y apariencia | Todo | Según permiso | Lectura opcional | No |
| Crear y editar noticias | Todo | Según permiso | Sí | No |
| Publicar o programar noticias | Todo | Según permiso | Según permiso | No |
| Categorías, etiquetas y multimedia | Todo | Según permiso | Según permiso | No |
| Programas y horarios | Todo | Según permiso | Lectura opcional | Asignados |
| Estadísticas y actividad | Todo | Módulos delegados | Editoriales | Propias |

La autorización será granular por módulo y acción mediante roles, permisos,
middleware y Policies de Laravel. El superadministrador omite las restricciones
mediante una regla central controlada; los demás usuarios solo reciben permisos
explícitos.

Un administrador:

- solo administra los módulos que le asignó un superadministrador;
- puede crear editores y locutores, pero no administradores ni
  superadministradores;
- no puede conceder permisos que él mismo no posee;
- no puede editar, desactivar ni elevar una cuenta de mayor jerarquía.

No se ocultarán solamente los botones: cada petición será verificada nuevamente
en el servidor. Los cambios de rol, permiso, activación y contraseña quedarán en
la auditoría.

## 5. Navegación prevista del dashboard

```text
/admin
├── Resumen y estadísticas
├── Noticias
│   ├── Todas
│   ├── Nueva noticia
│   ├── Categorías
│   └── Etiquetas
├── Multimedia
├── Radio y video
│   ├── Señales
│   ├── Programas
│   └── Programación
├── Apariencia
│   ├── Hero de portada
│   ├── Secciones y sidebars
│   ├── Sliders
│   └── Publicidad
├── Configuración
│   ├── Datos del portal
│   ├── Redes sociales
│   ├── SEO
│   ├── Usuarios
│   ├── Roles y permisos
│   └── Seguridad
└── Actividad
```

Todas las rutas administrativas utilizarán el prefijo `/admin`, nombres
`admin.*` y middleware `auth`, `active` y el permiso correspondiente.

## 6. Fases de implementación

### Fase 1 — Acceso, seguridad y estructura del panel

#### Trabajo

- Implementar login y logout con correo y contraseña.
- Incorporar CAPTCHA matemático de números o suma, generado y validado en el
  servidor, de un solo uso y con expiración.
- Permitir configurar si el CAPTCHA aparece siempre o después de varios
  intentos fallidos; nunca sustituirá el rate limiting.
- Implementar “Olvidé mi contraseña” mediante correo, token firmado, de un solo
  uso y con expiración; limitar solicitudes y cerrar sesiones anteriores al
  completar el cambio.
- Implementar cambio voluntario de contraseña y cambio obligatorio durante el
  primer acceso de una cuenta nueva o restablecida.
- Usar hash seguro de Laravel, regeneración de sesión, cookies `HttpOnly`,
  `Secure` y `SameSite`, protección CSRF, bloqueo progresivo y auditoría.
- Ampliar `users` con estado, foto opcional, `must_change_password`,
  `password_changed_at`, `last_login_at`, `failed_login_attempts` y
  `locked_until`.
- Crear tablas de roles, permisos, asignación usuario/rol y permisos por rol,
  evitando un único campo rígido para toda la autorización.
- Crear middleware de usuario activo y Policies.
- Crear layout administrativo responsive, menú, breadcrumbs y mensajes.
- Crear un comando interactivo para generar el primer superadministrador,
  solicitando la contraseña de forma oculta.
- Separar visualmente el dashboard del portal público.

#### Cuenta maestra inicial

- Correo inicial: `superadmin@gmail.com`.
- La contraseña inicial será la credencial privada proporcionada por el
  propietario y deberá cambiarse obligatoriamente en el primer acceso.
- La contraseña no se escribirá en este documento, seeders, migraciones,
  historial Git, registros ni capturas.
- El alta se realizará con un comando similar a
  `php artisan admin:create-superadmin superadmin@gmail.com`, que solicitará la
  clave de manera interactiva y almacenará solamente su hash.
- En producción se exigirá una contraseña robusta distinta de la credencial
  temporal de desarrollo.

#### Criterios de aceptación

- Un visitante no puede acceder a `/admin`.
- Un usuario desactivado no puede iniciar sesión.
- Cada rol recibe únicamente sus acciones autorizadas.
- El CAPTCHA rechaza respuestas vencidas, reutilizadas o incorrectas.
- La recuperación usa un enlace temporal de un solo uso y no revela si un
  correo existe.
- El administrador no puede ampliar sus propios permisos ni crear cuentas de
  mayor jerarquía.
- Una cuenta nueva debe cambiar su contraseña antes de usar el dashboard.
- No existe una contraseña administrativa incluida en el repositorio.
- Login, logout, recuperación, CAPTCHA, bloqueo, CSRF, sesiones y permisos
  tienen pruebas.

### Fase 2 — Biblioteca multimedia

Esta fase precede a los formularios de noticias y publicidad porque todos ellos
necesitan seleccionar imágenes.

#### Datos

Crear `media` con:

- nombre y ruta;
- disco;
- MIME, extensión y tamaño;
- ancho y alto;
- texto alternativo;
- crédito y licencia;
- usuario que subió el archivo;
- fecha y metadatos de uso.

#### Trabajo

- Subida múltiple y selección desde un modal.
- Validación real de MIME, extensión, dimensiones y peso.
- Generación de versiones optimizadas para portada, tarjetas y artículos.
- Conversión a WebP cuando el servidor lo permita.
- Búsqueda por nombre, crédito y fecha.
- Prevención del borrado de archivos que estén siendo utilizados.
- Enlace `storage:link` y alternativa documentada para hosting compartido.

#### Criterios de aceptación

- Un editor puede subir una imagen una vez y reutilizarla.
- El sistema obliga a registrar texto alternativo.
- Archivos peligrosos o excesivamente grandes son rechazados.
- El borrado no deja imágenes rotas en el portal.

### Fase 3 — Gestión editorial de noticias

#### Ampliaciones de datos

- Relacionar la imagen principal con `media`.
- Añadir `created_by`, `updated_by` y, si se requiere, `reviewed_by`.
- Incorporar estados:
  `draft`, `in_review`, `scheduled`, `published`, `archived`.
- Añadir `scheduled_for`, `published_at` y eliminación recuperable.
- Añadir título SEO, descripción SEO y datos Open Graph opcionales.

#### Pantallas

- Tabla con filtros por estado y categoría, selector de 10, 20, 50 o 100
  registros y paginación conservando la consulta.
- Formulario de creación y edición.
- Selector de categoría, etiquetas y multimedia.
- **Tiptap Rich Text Editor** como editor oficial del cuerpo de noticias.
- Vista previa sin publicar.
- Acciones de enviar a revisión, aprobar, programar, publicar y archivar.
- Duplicar noticia.
- Acciones visuales de vista previa, edición y eliminación recuperable.
- Papelera filtrable y restauración desde el mismo listado.

#### Configuración de Tiptap

- Barra editorial profesional con párrafos, encabezados permitidos, negrita,
  cursiva, subrayado, tachado, color, resaltado, alineación, listas, citas,
  enlaces, tablas, código y deshacer/rehacer.
- Inserción de imágenes desde la biblioteca multimedia, conservando texto
  alternativo, pie, crédito y licencia.
- Videos de YouTube mediante un bloque controlado y dominio autorizado; no se
  aceptan scripts ni iframes libres.
- Autoguardado de borrador, aviso de cambios sin guardar, contador de palabras
  y caracteres, modo de pantalla completa y vista previa responsive.
- Salida HTML semántica y compatible con el diseño del artículo público.

#### Reglas

- El slug se sugiere desde el título, pero sigue siendo editable y único.
- Un editor sin permiso de publicación no puede aprobarse a sí mismo.
- Una noticia programada se publica mediante el scheduler.
- El backend sanitiza el HTML con una lista permitida; nunca confía únicamente
  en la configuración del editor del navegador.
- La fuente, URL, crédito y licencia continúan disponibles.

#### Criterios de aceptación

- Todo el contenido de `/noticias` puede administrarse desde el panel.
- Los cambios se reflejan sin editar seeders.
- Categorías, etiquetas, búsquedas y vistas relacionadas siguen funcionando.
- Publicar, despublicar, programar y recuperar tienen pruebas.
- El menú Noticias agrupa en un flyout Crear noticia, Todas las noticias,
  Categorías y Etiquetas.

### Fase 4 — Categorías, etiquetas y portada editorial

#### Trabajo

- CRUD de categorías con color, descripción, estado y slug.
- Ordenamiento mediante arrastrar y soltar.
- Controles de visibilidad en menú y portada.
- Relevancia, límite y diseño de sección.
- CRUD de etiquetas y combinación de duplicados.
- Interfaz para prioridad editorial de noticias.

#### Hero

Administrar `home.hero_rotator`:

- modo automático/manual;
- intervalo;
- bucle;
- efecto y parallax;
- cantidad de noticias;
- selección y orden manual.

#### Slider de más vistas

Mover su configuración provisional de `.env` a `portal_settings`:

- automático/manual;
- intervalo;
- bucle;
- cantidad de noticias;
- periodo utilizado para calcular popularidad.

#### Criterios de aceptación

- El administrador puede cambiar Regionales, Locales u otra categoría a la
  primera posición y la portada lo respeta.
- El hero puede cambiarse de automático a manual sin desplegar código.
- Una categoría desactivada desaparece de las ubicaciones configuradas.

### Fase 5 — Programas y programación semanal

#### Trabajo

- CRUD de programas con nombre, slug, resumen, descripción, conductores,
  imagen y estado.
- Selector de imagen desde multimedia.
- Editor semanal de horarios por día.
- Copiar una programación a varios días.
- Validar cruces de horario.
- Activar, desactivar y reordenar programas.
- Vista previa de `/programas` y `/programacion`.

#### Evolución opcional

Crear una tabla `hosts` solamente si el cliente necesita perfiles individuales
de conductores. Para la primera versión se puede conservar el campo de texto
actual.

#### Criterios de aceptación

- El administrador puede modificar toda la parrilla sin tocar la base de datos.
- No se guardan dos espacios incompatibles en el mismo horario.
- El bloque “Ahora en vivo” refleja automáticamente la programación.

### Fase 6 — Streaming de audio y video

#### Audio

- Nombre, URL HTTPS, formato MP3/AAC/HLS, portada y estado.
- Selección de señal principal.
- Orden de señales.
- Botón de comprobación técnica de URL.

#### Video

- YouTube, HLS o iframe permitido.
- Activación opcional.
- Vista previa antes de guardar.
- Lista controlada de proveedores/hosts permitidos.

#### Reglas

- No aceptar streaming HTTP en un portal HTTPS.
- No guardar código `<script>` entregado por terceros.
- Mostrar una señal de respaldo o mensaje cuando el stream esté inactivo.
- Laravel no retransmitirá el audio ni consumirá el ancho de banda del stream.

#### Criterios de aceptación

- Cambiar la URL desde el dashboard actualiza el reproductor público.
- Desactivar video retira su módulo sin romper `/en-vivo`.
- Las validaciones impiden configuraciones inseguras.

### Fase 7 — Publicidad, sidebars y apariencia

#### Publicidad

Administrar la tabla `advertisements`:

- nombre interno;
- ubicación;
- imagen;
- texto alternativo;
- enlace y apertura en nueva pestaña;
- orden;
- activación;
- fecha de inicio y finalización.

Ubicaciones iniciales:

- portada;
- sidebar de artículo;
- sidebar de secciones;
- interior de artículo;
- programas/programación.

#### Sidebars

Editar `article.sidebar` y `section.sidebar`:

- módulos visibles;
- orden/prioridad;
- cantidades;
- comportamiento sticky;
- comportamiento adaptativo.

#### Configuración visual

- enlaces de redes sociales;
- textos del footer;
- datos de contacto;
- logo, favicon y colores permitidos;
- banners de la portada.

#### Criterios de aceptación

- Un anuncio aparece y vence según las fechas configuradas.
- Desactivarlo lo retira inmediatamente.
- El orden del sidebar cambia sin editar Blade.
- El ajuste adaptativo de altura se conserva.

### Fase 8 — Dashboard, actividad y mantenimiento

#### Dashboard estadístico

- tarjetas de noticias publicadas, borradores, pendientes y programadas;
- visitas totales y publicaciones del periodo;
- contenido pendiente de revisión;
- publicaciones programadas;
- noticias más vistas;
- estado de audio/video;
- espacios publicitarios activos y próximos a vencer;
- actividad editorial reciente.

Gráficos iniciales:

- publicaciones y visitas por día, semana o mes;
- noticias por categoría y estado editorial;
- ranking de noticias más leídas;
- rendimiento de categorías configuradas como relevantes;
- reproducciones o clics de audio/video cuando exista consentimiento y
  medición confiable;
- impresiones y clics de publicidad cuando se habilite esa medición.

Todos los gráficos admitirán periodos de 7, 30 y 90 días y rango personalizado.
La información se limitará según el rol: el superadministrador ve todo, un
administrador sus módulos delegados, un editor la operación editorial y un
locutor sus programas. Cada gráfico tendrá resumen tabular accesible.

Para que funcione bien en hosting compartido se usarán agregados diarios,
consultas indexadas y caché; no se cargarán eventos sin límite en cada visita.
No se almacenarán datos personales innecesarios. Las métricas disponibles se
definirán antes de mostrar cifras y se diferenciarán datos reales de estados sin
información.

#### Criterios de aceptación del dashboard

- Los indicadores coinciden con consultas verificables de la base de datos.
- Los filtros actualizan tarjetas, gráficos y tablas con el mismo periodo.
- Un usuario no puede consultar estadísticas fuera de sus permisos.
- Los estados sin datos se muestran claramente y nunca se rellenan con cifras
  simuladas.
- Las consultas principales tienen índices, caché y pruebas de autorización.

#### Auditoría

Crear `activity_logs` con:

- usuario;
- acción;
- entidad e identificador;
- cambios relevantes;
- fecha e IP resumida cuando corresponda.

Registrar como mínimo:

- publicación o despublicación;
- cambios de streaming;
- cambios de usuarios y roles;
- publicidad;
- configuración global.

#### Mantenimiento

- papelera y restauración;
- exportación de respaldo de configuración;
- cachés administrables;
- estado del scheduler y almacenamiento;
- guía de backup de base de datos y archivos.

### Fase 9 — SEO, páginas y calidad editorial

- Sitemap dinámico.
- `robots.txt` según entorno.
- canonical URLs.
- Open Graph y tarjetas sociales.
- datos estructurados para `NewsArticle` y organización.
- páginas institucionales mediante `pages`.
- contacto y política de privacidad si el cliente los requiere.
- redirecciones para slugs modificados.
- revisión de accesibilidad con teclado, contraste y lectores de pantalla.

### Fase 10 — PWA y producción

La PWA abarcará todo el portal público, no solamente el reproductor.

- manifest, nombre, colores e iconos;
- service worker;
- página offline;
- estrategia de caché para layout y noticias visitadas;
- no cachear de forma agresiva streams ni dashboard;
- instalación en Android;
- actualización segura de versiones;
- pruebas Lighthouse;
- despliegue en hosting compartido;
- cron para `schedule:run`;
- optimización y cachés de producción.

## 7. Orden recomendado de ejecución

```text
Seguridad y acceso
        ↓
Biblioteca multimedia
        ↓
Noticias + categorías + etiquetas
        ↓
Programas + horarios + streaming
        ↓
Apariencia + publicidad + configuración
        ↓
Actividad + SEO + páginas
        ↓
PWA + despliegue
```

La interfaz del dashboard no debe comenzar por gráficos o estadísticas. Primero
debe resolver correctamente autenticación, permisos y captura de datos. Los
gráficos se construirán después sobre métricas reales.

## 8. Decisiones técnicas

- Blade y JavaScript ligero, coherentes con el portal actual.
- Sin SPA obligatoria.
- Tiptap se integrará como componente JavaScript aislado dentro del formulario
  Laravel, sin convertir todo el panel en una SPA.
- Formularios Laravel con Form Requests.
- Roles, permisos granulares, middleware y Policies para autorización.
- Transacciones para publicaciones, horarios y cambios relacionados.
- Paginación y filtros en todas las tablas administrativas.
- Jobs solamente para operaciones costosas como optimización de imágenes.
- Scheduler para publicación programada y vencimiento de campañas.
- SQLite en desarrollo y MySQL/MariaDB en producción.
- Archivos fuente, Blade, JSON, CSV y respuestas HTTP en UTF-8; páginas con
  `<meta charset="utf-8">` y cabeceras `Content-Type` correctas.
- MySQL/MariaDB con `utf8mb4` de extremo a extremo en conexión, tablas y
  columnas, evitando conversiones implícitas que produzcan mojibake.
- Importaciones externas detectarán o rechazarán codificaciones desconocidas y
  normalizarán el texto a UTF-8 antes de validarlo y persistirlo.
- Diseño compatible con hosting compartido y compilación previa de assets.

## 9. Estrategia de pruebas

Cada módulo deberá incluir:

- pruebas de acceso por rol;
- validaciones de formulario;
- creación, edición y eliminación/archivo;
- efecto del cambio en las páginas públicas;
- archivos y relaciones;
- estados vacíos y errores;
- pruebas responsive prioritarias;
- protección contra acciones no autorizadas.
- verificación de tildes, eñes, comillas tipográficas, símbolos monetarios y
  emojis en formularios, base de datos, API, exportaciones y páginas públicas;
- detección de patrones habituales de mojibake en el código activo.

Antes de cada entrega:

```bash
vendor/bin/pint --test
php artisan test
npm run build
```

## 10. Hitos de entrega

| Hito | Resultado demostrable |
| --- | --- |
| A | Login, CAPTCHA, recuperación, roles y estructura del dashboard |
| B | Multimedia y noticias administrables con Tiptap |
| C | Categorías, hero y sliders configurables |
| D | Programas, horarios y streaming configurables |
| E | Publicidad, sidebars, redes y apariencia |
| F | Dashboard estadístico, auditoría, SEO, páginas y mantenimiento |
| G | PWA y paquete validado para hosting |

## 11. Definición de “autoadministrable”

La primera versión se considerará autoadministrable cuando un administrador
pueda, sin modificar código:

1. crear usuarios respetando la jerarquía y los módulos delegados;
2. publicar una noticia completa con imagen, categoría y etiquetas;
3. cambiar lo destacado y el orden de portada;
4. modificar programas y horarios;
5. configurar audio y video;
6. activar o programar publicidad;
7. editar redes, sidebars y datos principales;
8. comprobar los cambios en el portal público;
9. recuperar contenido archivado;
10. identificar quién realizó un cambio sensible;
11. consultar indicadores y gráficos según su nivel de acceso.

## 12. Próximo bloque a ejecutar

Los hitos A, B y C están implementados. El siguiente bloque es el **Hito D**:

1. CRUD de programas y perfiles de locutores;
2. programación semanal sin cruces de horario;
3. configuración de señales de audio;
4. configuración opcional de video;
5. estado y señal alternativa cuando el proveedor no esté disponible;
6. pruebas de reproductor, horarios y permisos de locutor.

La PWA y las estadísticas avanzadas permanecen en sus hitos posteriores.
