# Plan del sistema autoadministrable

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
- consultar un resumen de actividad y contenido pendiente.

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

## 4. Roles y permisos

| Acción | Administrador | Editor | Autor |
| --- | --- | --- | --- |
| Acceder al dashboard | Sí | Sí | Sí |
| Administrar usuarios | Sí | No | No |
| Configuración general y streaming | Sí | No | No |
| Publicidad y apariencia | Sí | Lectura | No |
| Crear noticias | Sí | Sí | Sí |
| Editar cualquier noticia | Sí | Sí | No |
| Editar noticias propias | Sí | Sí | Sí |
| Publicar o programar | Sí | Sí | No |
| Archivar/restaurar | Sí | Sí | Solo propias |
| Categorías y etiquetas | Sí | Sí | Lectura |
| Programas y horarios | Sí | Sí | Lectura |
| Biblioteca multimedia | Sí | Sí | Sí |

Los permisos se implementarán con middleware y Policies de Laravel. No se
ocultarán solamente los botones: el servidor verificará cada acción.

## 5. Navegación prevista del dashboard

```text
/admin
├── Resumen
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
│   └── Usuarios
└── Actividad
```

Todas las rutas administrativas utilizarán el prefijo `/admin`, nombres
`admin.*` y middleware `auth`, `active` y el permiso correspondiente.

## 6. Fases de implementación

### Fase 1 — Acceso, seguridad y estructura del panel

#### Trabajo

- Implementar login, logout, recuperación/cambio de contraseña y limitación de
  intentos.
- Ampliar `users` con `role`, `is_active`, `last_login_at` y foto opcional.
- Crear middleware de usuario activo y Policies.
- Crear layout administrativo responsive, menú, breadcrumbs y mensajes.
- Crear comando seguro para generar el primer administrador.
- Separar visualmente el dashboard del portal público.

#### Criterios de aceptación

- Un visitante no puede acceder a `/admin`.
- Un usuario desactivado no puede iniciar sesión.
- Cada rol recibe únicamente sus acciones autorizadas.
- No existe una contraseña administrativa incluida en el repositorio.
- Login, logout, CSRF, rate limiting y permisos tienen pruebas.

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

- Tabla con filtros por estado, categoría, autor y fecha.
- Formulario de creación y edición.
- Selector de categoría, etiquetas y multimedia.
- Editor de contenido con formato controlado.
- Vista previa sin publicar.
- Acciones de enviar a revisión, aprobar, programar, publicar y archivar.
- Duplicar noticia.

#### Reglas

- El slug se sugiere desde el título, pero sigue siendo editable y único.
- Un autor no puede publicarse a sí mismo.
- Una noticia programada se publica mediante el scheduler.
- El HTML se limpia para evitar scripts o contenido inseguro.
- La fuente, URL, crédito y licencia continúan disponibles.

#### Criterios de aceptación

- Todo el contenido de `/noticias` puede administrarse desde el panel.
- Los cambios se reflejan sin editar seeders.
- Categorías, etiquetas, búsquedas y vistas relacionadas siguen funcionando.
- Publicar, despublicar, programar y recuperar tienen pruebas.

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

#### Dashboard inicial

- noticias por estado;
- contenido pendiente de revisión;
- publicaciones programadas;
- noticias más vistas;
- estado de audio/video;
- espacios publicitarios activos y próximos a vencer;
- actividad editorial reciente.

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
debe resolver correctamente autenticación, permisos y formularios de contenido.

## 8. Decisiones técnicas

- Blade y JavaScript ligero, coherentes con el portal actual.
- Sin SPA obligatoria.
- Formularios Laravel con Form Requests.
- Policies para permisos.
- Transacciones para publicaciones, horarios y cambios relacionados.
- Paginación y filtros en todas las tablas administrativas.
- Jobs solamente para operaciones costosas como optimización de imágenes.
- Scheduler para publicación programada y vencimiento de campañas.
- SQLite en desarrollo y MySQL/MariaDB en producción.
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

Antes de cada entrega:

```bash
vendor/bin/pint --test
php artisan test
npm run build
```

## 10. Hitos de entrega

| Hito | Resultado demostrable |
| --- | --- |
| A | Login, roles y estructura del dashboard |
| B | Multimedia y noticias administrables |
| C | Categorías, hero y sliders configurables |
| D | Programas, horarios y streaming configurables |
| E | Publicidad, sidebars, redes y apariencia |
| F | Auditoría, SEO, páginas y mantenimiento |
| G | PWA y paquete validado para hosting |

## 11. Definición de “autoadministrable”

La primera versión se considerará autoadministrable cuando un administrador
pueda, sin modificar código:

1. crear usuarios editoriales;
2. publicar una noticia completa con imagen, categoría y etiquetas;
3. cambiar lo destacado y el orden de portada;
4. modificar programas y horarios;
5. configurar audio y video;
6. activar o programar publicidad;
7. editar redes, sidebars y datos principales;
8. comprobar los cambios en el portal público;
9. recuperar contenido archivado;
10. identificar quién realizó un cambio sensible.

## 12. Primer bloque a ejecutar

La implementación debe comenzar por el **Hito A**:

1. migración de roles y estado de usuarios;
2. autenticación;
3. Policies y middleware;
4. layout `/admin`;
5. comando para crear el primer administrador;
6. pruebas de acceso y seguridad.

No se debe comenzar aún con PWA ni estadísticas avanzadas.
