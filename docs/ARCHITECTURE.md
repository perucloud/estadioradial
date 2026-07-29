# Arquitectura inicial

## Principios

1. Laravel será la única aplicación de producción.
2. `oldweb/` no se publicará ni se usará como dependencia.
3. El contenido se almacenará en base de datos y se resolverá mediante slugs.
4. Cabecera, navegación, reproductor y pie serán componentes reutilizables.
5. El stream se consumirá directamente desde el proveedor para no cargar el
   ancho de banda del hosting.
6. El portal público completo será la futura PWA.

## Rutas públicas previstas

| Ruta | Propósito |
| --- | --- |
| `/` | Portada |
| `/noticias` | Últimas noticias |
| `/noticias/territorios` | Noticias regionales por ubicación |
| `/noticias/territorios/{país}/{región?}/{provincia?}/{distrito?}` | Portada territorial jerárquica |
| `/noticias/{categoria}` | Noticias de una categoría |
| `/noticias/{categoria}/{noticia}` | Detalle de noticia |
| `/programas` | Listado de programas |
| `/programas/{programa}` | Detalle de programa |
| `/programacion` | Parrilla semanal |
| `/en-vivo` | Radio y video en vivo |
| `/paginas/{pagina}` | Páginas institucionales |
| `/contacto` | Formulario de contacto |

## Entidades principales

- `users`
- `posts`
- `categories`
- `tags`
- `programs`
- `hosts`
- `schedules`
- `episodes`
- `streams`
- `media`
- `banners`
- `pages`
- `settings`

## Frontend

- Blade para renderizado del servidor.
- Componentes Blade para la estructura compartida.
- JavaScript ligero para navegación y reproducción.
- Navegación compatible con un reproductor persistente.
- Recursos compilados antes del despliegue.

## Streaming

La primera versión contemplará audio MP3/AAC y HLS, video HLS y emisiones de
YouTube. Una instalación HTTPS requerirá fuentes de streaming HTTPS. Las URL se
validarán antes de activarlas y el navegador no iniciará audio automáticamente.

## Referencia heredada

La carpeta `oldweb/` contiene una captura de Drupal 7 generada por HTTrack. Sus
HTML, formularios y scripts no forman parte de la nueva aplicación. Solo se
consultarán para reproducir la distribución visual y localizar recursos
temporales autorizados.
