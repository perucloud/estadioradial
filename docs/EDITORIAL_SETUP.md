# Gestión editorial y multimedia

## Módulos

El Hito B añade:

- `/admin/multimedia`: biblioteca de imágenes reutilizables;
- `/admin/noticias`: listado y filtros editoriales;
- `/admin/noticias/nueva`: editor completo con CKEditor 5;
- vista previa privada antes de publicar;
- estados de borrador, revisión, programación, publicación y archivo.

## Biblioteca multimedia

Las cargas admiten JPG, PNG, WebP y GIF, con un máximo de 8 MB y 6000 píxeles
por lado. Los SVG subidos por usuarios se rechazan.

El texto alternativo, el pie, el crédito y la licencia son opcionales. Cuando
no se proporciona texto alternativo, se genera una descripción básica a partir
del nombre del archivo. Cuando GD y WebP están disponibles se generan variantes:

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

## Editor CKEditor 5

CKEditor 5 funciona como componente JavaScript aislado dentro del formulario
Blade y se compila con Vite. Incluye:

- encabezados H2–H4;
- negrita, cursiva, subrayado y tachado;
- colores, alineación, listas, sangría, citas y separadores;
- enlaces, tablas y contenido multimedia;
- imágenes seleccionadas desde la biblioteca;
- deshacer y rehacer;
- contador de palabras y vista de código fuente;
- copia local automática y aviso de cambios sin guardar.

El navegador entrega HTML semántico. El servidor vuelve a procesarlo con
Symfony HTML Sanitizer y una lista explícita de elementos y atributos. Scripts,
iframes, eventos, estilos y esquemas de URL no autorizados se descartan.

La clave de licencia se configura como `VITE_CKEDITOR_LICENSE_KEY`. El valor
`GPL` solo debe utilizarse cuando la licencia completa del proyecto sea
compatible con GPL 2.0 o posterior; en otro caso debe utilizarse una clave
comercial de CKEditor.

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
