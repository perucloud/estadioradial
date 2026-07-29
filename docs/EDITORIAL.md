# Configuración editorial

La portada no depende solamente de la fecha. Cada categoría tiene:

- `display_order`: posición en el menú.
- `relevance_weight`: importancia relativa para seleccionar noticias.
- `show_in_menu` y `show_on_home`: visibilidad independiente.
- `homepage_limit` y `homepage_layout`: base para los módulos del futuro panel.

Cada noticia dispone de `editorial_priority`, fijación temporal mediante
`pinned_until`, ocultamiento de portada, fuente, enlace, crédito y licencia de
imagen. Los asuntos específicos se clasifican con etiquetas.

## Edición y listado administrativo

El flyout **Noticias** del dashboard reúne creación, listado, categorías y
etiquetas. El listado admite 10, 20, 50 o 100 registros por página, conserva
los filtros durante la paginación y ofrece acciones iconográficas de vista
previa, edición y eliminación.

Eliminar utiliza `SoftDeletes`: la noticia pasa al filtro **Papelera** y puede
restaurarse sin perder contenido, relaciones ni metadatos.

CKEditor 5 ofrece formato editorial, colores controlados, alineación, listas,
citas, enlaces, biblioteca Media, tablas, código fuente, multimedia y pantalla
completa. El backend vuelve a sanitizar siempre el HTML. Solo conserva
propiedades visuales permitidas y proveedores multimedia expresamente
autorizados.

## Cambiar la prioridad

El responsable del portal administra categorías, relevancia y orden desde:

```text
/admin/categorias
```

La pantalla permite crear, editar, activar, ocultar y ordenar categorías. Como
alternativa operativa, el orden también puede definirse desde la terminal:

```bash
php artisan editorial:prioritize regionales locales politica economia nacional
```

Para retirar de la portada las categorías no incluidas:

```bash
php artisan editorial:prioritize locales regionales deportes --hide-missing
```

El primer slug recibe la mayor relevancia. El comando valida que todos los slugs
existan antes de guardar cambios.

## Slider de noticias más vistas

El slider se configura en:

```text
/admin/apariencia/portada
```

El dashboard controla:

- movimiento automático o manual;
- intervalo;
- repetición;
- cantidad de tarjetas;
- periodo de publicaciones utilizado por el ranking.

La configuración se guarda en `home.most_viewed_slider`; ya no depende de
variables de `.env`. Mientras no exista analítica histórica diaria, el periodo
filtra la fecha de publicación y el orden utiliza las lecturas acumuladas.

## Columna lateral de artículos

La página de noticia consulta la configuración `article.sidebar` de la tabla
`portal_settings`. Allí se controlan:

- orden y visibilidad de los módulos;
- cantidad de noticias más leídas y últimas noticias;
- comportamiento fijo de la columna en escritorio.

Los enlaces de redes se guardan en `social.links`. Los anuncios se administran
como registros independientes en `advertisements`, con ubicación, imagen, texto
alternativo, URL de destino, orden, estado y fechas de vigencia. El dashboard
visual deberá editar estos registros y no introducir contenido directamente en
las plantillas.

El correo público se obtiene de `site.contact.email`. Este dato alimenta el
acceso de correo del menú hamburguesa y del pie de página. Ambos lugares también
ofrecen un acceso a `/admin`; cuando no existe una sesión autenticada, el
middleware conduce al formulario de ingreso y conserva el destino solicitado.

Las páginas de noticias, categorías, búsquedas, programas y programación usan
la configuración independiente `section.sidebar` y anuncios con la ubicación
`section_sidebar`. En escritorio, el modo `adaptive` compara la altura del
contenido principal y retira módulos desde el final del orden configurado hasta
evitar una columna desproporcionada. Si solo queda el primer módulo, reduce sus
últimos elementos; en móvil restaura todo el contenido debajo de la sección.

## Hero rotatorio de portada

La configuración `home.hero_rotator` controla la rotación del bloque principal:

- `mode`: `automatic` o `manual`;
- `interval`: intervalo en milisegundos, inicialmente `8000`;
- `loop`, `effect` y `parallax`;
- `news_limit`: entre cuatro y ocho noticias participantes;
- `selection_mode`: selección editorial `automatic` o `manual`;
- `post_ids`: orden explícito cuando la selección es manual.

Estos valores y las prioridades individuales de cada noticia se administran en
`/admin/apariencia/portada`. El dashboard también permite fijar una noticia
temporalmente, marcarla como destacada u ocultarla de la portada.

## Etiquetas

La ruta `/admin/etiquetas` permite crear, editar y eliminar etiquetas sin uso.
Cuando existen duplicados, la acción **Combinar** mueve todas las relaciones a
la etiqueta elegida antes de retirar la anterior.

La interfaz ofrece flechas, indicadores, pausa, teclado y gesto horizontal. El
autoplay se detiene durante la interacción, fuera de pantalla y al ocultar la
pestaña. La preferencia del sistema para reducir movimiento desactiva el avance
automático y simplifica las transiciones.

## Criterio de fuentes

Los textos del lote inicial son resúmenes editoriales propios. Cada registro
conserva la fuente consultada y su enlace. Las ilustraciones incluidas son
recursos propios del prototipo; no se descargan fotografías de terceros sin una
licencia comprobada.
