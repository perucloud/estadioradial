# Configuración editorial

La portada no depende solamente de la fecha. Cada categoría tiene:

- `display_order`: posición en el menú.
- `relevance_weight`: importancia relativa para seleccionar noticias.
- `show_in_menu` y `show_on_home`: visibilidad independiente.
- `homepage_limit` y `homepage_layout`: base para los módulos del futuro panel.

Cada noticia dispone de `editorial_priority`, fijación temporal mediante
`pinned_until`, ocultamiento de portada, fuente, enlace, crédito y licencia de
imagen. Los asuntos específicos se clasifican con etiquetas.

## Cambiar la prioridad

Hasta incorporar la interfaz visual del panel, el responsable del portal puede
definir el orden desde la terminal:

```bash
php artisan editorial:prioritize regionales locales politica economia nacional
```

Para retirar de la portada las categorías no incluidas:

```bash
php artisan editorial:prioritize locales regionales deportes --hide-missing
```

El primer slug recibe la mayor relevancia. El comando valida que todos los slugs
existan antes de guardar cambios.

## Criterio de fuentes

Los textos del lote inicial son resúmenes editoriales propios. Cada registro
conserva la fuente consultada y su enlace. Las ilustraciones incluidas son
recursos propios del prototipo; no se descargan fotografías de terceros sin una
licencia comprobada.
