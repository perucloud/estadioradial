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

## Categorías editoriales jerárquicas

El administrador de `/admin/categorias` admite categorías principales y
subcategorías. Cada registro conserva categoría superior opcional, icono,
color, descripción, visibilidad, orden, relevancia y metadatos SEO.

La pantalla ofrece:

- árbol editorial con profundidad visual;
- búsqueda y filtros por estado y categoría superior;
- paginación de 10, 20, 50 o 100 registros;
- conteo de noticias y subcategorías;
- prevención de ciclos padre-hijo;
- papelera y restauración;
- reasignación obligatoria de noticias antes de retirar una categoría;
- promoción segura de sus subcategorías al nivel superior.

Esta jerarquía se reserva para temas, por ejemplo `Deportes > Fútbol`. Países,
regiones, provincias y distritos pertenecerán al módulo independiente de
ubicaciones.

## Ubicaciones geográficas

La ruta `/admin/ubicaciones` administra una taxonomía territorial separada:

```text
País
└── Región
    └── Provincia
        └── Distrito
```

El sistema valida que cada nivel dependa del tipo correcto, admite código ISO
de país, UBIGEO, coordenadas, descripción y metadatos SEO. También incluye
búsqueda, filtros, orden, paginación, papelera y restauración.

La carga inicial incorpora `Perú > Moquegua`, las provincias Mariscal Nieto,
General Sánchez Cerro e Ilo, y los distritos registrados de Mariscal Nieto.
También registra las 25 regiones del Perú como base para ampliar progresivamente
sus provincias y distritos desde el dashboard.
Esta estructura todavía no clasifica publicaciones: su relación con noticias
se implementa mediante una ubicación primaria opcional.

## Ubicación de las noticias

Crear y editar noticias incluye selectores dependientes:

```text
País → Región → Provincia → Distrito
```

El editor puede detener la selección en cualquier nivel. Por ejemplo:

- `Perú` representa alcance nacional;
- `Perú → Moquegua` representa alcance regional;
- `Perú → Moquegua → Mariscal Nieto` representa alcance provincial;
- `Perú → Moquegua → Mariscal Nieto → Carumas` representa alcance distrital;
- ningún valor representa una noticia sin localización específica.

La noticia guarda la ubicación más precisa seleccionada. El servidor comprueba
la relación completa aun cuando JavaScript esté desactivado. Las ubicaciones
utilizadas por publicaciones no pueden eliminarse hasta reasignar esas
noticias.

## Publicación territorial

La portada obtiene “Noticias regionales” por la ubicación de la publicación,
no por una categoría editorial. Por ello, una noticia puede ser al mismo tiempo:

```text
Categoría: Política
Ubicación: Perú → Moquegua → Mariscal Nieto → Carumas
```

El portal dispone de:

- `/noticias/territorios` para toda la cobertura regional;
- páginas jerárquicas por país, región, provincia y distrito;
- breadcrumbs territoriales en listados y artículos;
- inclusión de noticias de niveles inferiores: la página de Moquegua también
  muestra noticias de sus provincias y distritos.

Para revisar noticias antiguas de las categorías `Regionales` y `Locales`:

```bash
php artisan editorial:migrate-locations
```

El comando trabaja primero como vista previa. Solo relaciona coincidencias
inequívocas con ubicaciones existentes. Para guardar el resultado:

```bash
php artisan editorial:migrate-locations --apply
```

Las publicaciones que no coincidan con un territorio registrado permanecen sin
ubicación para que un editor las revise; nunca se asignan a una región por
suposición. El listado administrativo de noticias ofrece el filtro
`Sin ubicación` para localizar y completar esos registros.

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

## Catálogos geográficos

La clasificación temática y el alcance territorial son independientes. Una
noticia puede ser `Política` y, al mismo tiempo, corresponder a Perú, Moquegua,
Mariscal Nieto o Carumas.

El sistema utiliza dos catálogos locales y versionados:

- `millan2993/countries`, exclusivamente para los nombres de países;
- `RitchieRD/ubigeos-peru-data`, para los 25 departamentos (mostrados como
  regiones), 196 provincias y 1,892 distritos del Perú.

Al seleccionar un país, el formulario consulta sus regiones bajo demanda. Las
provincias y distritos se cargan del mismo modo, evitando insertar más de dos
mil opciones en el HTML inicial. Fuera de Perú basta con detenerse en el país;
el sistema no importa estados ni ciudades extranjeras.

Los catálogos no dependen de Internet durante la edición. Para volver a
sincronizarlos desde las copias incluidas en `database/data`:

```bash
php artisan locations:import-catalogs
```

La sincronización es idempotente, conserva los IDs que ya usan las noticias y
reconcilia los registros por fuente, UBIGEO y jerarquía. El panel mantiene la
opción de crear una ubicación personalizada cuando el catálogo no cubra un caso
especial.

## Programas, horarios y streaming

El Hito D se administra desde tres módulos:

- `/admin/programas`: contenido público, imagen de Media, orden, estado y
  asociación con usuarios de rol Locutor;
- `/admin/programacion-radial`: parrilla semanal, copia a varios días y
  validación de cruces entre espacios activos;
- `/admin/streaming`: señales de audio y video, formato, prioridad, portada,
  estado y mensaje alternativo.

Las señales solo admiten URL HTTPS. El hosting no retransmite el contenido: el
reproductor público se conecta directamente al proveedor. Cada tipo puede tener
una sola señal principal y, cuando no hay una señal activa, `/en-vivo` presenta
el mensaje alternativo configurado sin romper la página.
