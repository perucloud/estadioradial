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
- resumen público automático, editable manualmente;
- copia local automática y aviso de cambios sin guardar.

En noticias nuevas, la fecha visible se inicializa con la hora local actual.
La publicación inmediata siempre registra la hora real de la acción; para
programar se debe seleccionar una fecha futura.

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

En desarrollo, el proceso que mantiene activo el programador es:

```bash
php artisan schedule:work
```

`composer run dev` ya incluye ese proceso. Si se utiliza el dominio virtual de
Laragon sin ejecutar Composer, debe iniciarse `schedule:work` en segundo plano.

El scheduler ejecuta el comando cada minuto. En hosting debe configurarse un
único cron, reemplazando la ruta por la ruta absoluta del proyecto:

```text
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

La aplicación utiliza `America/Lima` como zona horaria predeterminada. Las
fechas se almacenan en UTC y se presentan al usuario en la zona horaria del
portal. El dashboard muestra el último latido del programador y alerta sobre
noticias vencidas pendientes.

## Verificación

```bash
vendor/bin/pint --test
php artisan test
npm run build
```
