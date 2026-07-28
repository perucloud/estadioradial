# Gestión editorial y multimedia

## Módulos

El Hito B añade:

- `/admin/multimedia`: biblioteca de imágenes reutilizables;
- `/admin/noticias`: listado y filtros editoriales;
- `/admin/noticias/nueva`: editor completo con Tiptap;
- vista previa privada antes de publicar;
- estados de borrador, revisión, programación, publicación y archivo.

## Biblioteca multimedia

Las cargas admiten JPG, PNG, WebP y GIF, con un máximo de 8 MB y 6000 píxeles
por lado. Los SVG subidos por usuarios se rechazan.

Cada archivo exige texto alternativo y admite pie, crédito y licencia. Cuando
GD y WebP están disponibles se generan variantes:

- `thumb`: selección y biblioteca;
- `card`: tarjetas editoriales;
- `article`: portada del artículo.

Los archivos se guardan en `storage/app/public/media`. Debe existir el enlace:

```bash
php artisan storage:link
```

Una imagen vinculada como portada o insertada dentro de una noticia no se puede
eliminar.

Las ilustraciones históricas incluidas con el portal se importan mediante:

```bash
php artisan db:seed --class=LegacyMediaSeeder --force
```

El importador solo acepta recursos confiables ubicados en
`public/images/demo`; no habilita la carga pública de SVG.

## Editor Tiptap

Tiptap 3 funciona como componente JavaScript aislado dentro del formulario
Blade. Incluye:

- encabezados H2–H4;
- negrita, cursiva, subrayado y tachado;
- listas, citas y separadores;
- enlaces;
- tablas;
- imágenes seleccionadas desde la biblioteca;
- deshacer y rehacer;
- contador de palabras;
- copia local automática y aviso de cambios sin guardar.

El navegador entrega HTML semántico. El servidor vuelve a procesarlo con
Symfony HTML Sanitizer y una lista explícita de elementos y atributos. Scripts,
iframes, eventos, estilos y esquemas de URL no autorizados se descartan.

Referencias técnicas:

- <https://tiptap.dev/docs/editor/getting-started/install/vanilla-javascript>
- <https://symfony.com/doc/current/html_sanitizer.html>

## Flujo editorial

| Estado | Función |
| --- | --- |
| `draft` | Trabajo privado |
| `in_review` | Pendiente de revisión |
| `scheduled` | Programada para una fecha futura |
| `published` | Visible públicamente |
| `archived` | Retirada, pero recuperable |

Un editor puede crear y enviar a revisión. Solo un usuario con
`news.publish` puede publicar o programar.

La publicación programada se procesa con:

```bash
php artisan posts:publish-scheduled
```

El scheduler ya ejecuta este comando cada minuto. En hosting debe configurarse
un único cron:

```text
* * * * * php /ruta/al/proyecto/artisan schedule:run
```

La aplicación utiliza `America/Lima` como zona horaria predeterminada.

## Verificación

```bash
vendor/bin/pint --test
php artisan test
npm run build
```
