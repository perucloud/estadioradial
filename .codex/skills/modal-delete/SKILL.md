---
name: modal-delete
description: Reemplazar confirmaciones nativas de eliminación por un diálogo profesional, seguro, accesible, centrado y adaptable. Usar cuando el usuario diga “aplica Modal Delete”, “usa Modal Delete”, solicite un modal de eliminación o quiera modernizar un confirm, popup o aviso destructivo en cualquier módulo.
---

# Modal Delete

Aplicar un único patrón reutilizable para confirmar eliminaciones o acciones destructivas sin usar `window.confirm()`.

## Especificación

- Centrar el diálogo en `100vw` y `100dvh` en escritorio, tablet y móvil.
- Mostrar icono de advertencia, título, nombre del elemento, explicación y aviso de irreversibilidad.
- Incluir acciones explícitas: `Cancelar` y `Eliminar definitivamente`, adaptando el verbo cuando corresponda.
- Usar rojo editorial para la acción destructiva y un botón secundario neutro para cancelar.
- Permitir cerrar con el botón `×`, el fondo, `Escape` y `Cancelar`.
- Conservar y devolver el foco al elemento que abrió el diálogo.
- Gestionar foco inicial, atributos ARIA y navegación por teclado.
- Bloquear confirmaciones repetidas mientras se procesa la acción.
- Respetar `prefers-reduced-motion`.
- Usar una entrada breve con opacidad, escala y desplazamiento; evitar animaciones decorativas excesivas.
- No abrir el diálogo cuando la acción esté deshabilitada o protegida por relaciones existentes.

## Integración en Estación Radial

1. Reutilizar `resources/js/admin-delete-modal.js` y los estilos `.admin-delete-modal` de `resources/css/admin.css`.
2. Marcar el formulario con `data-confirm-delete`.
3. Proporcionar el mensaje en `data-confirm-delete`, el nombre en `data-confirm-name` y, cuando sea necesario, el título y texto del botón mediante `data-confirm-title` y `data-confirm-button`.
4. Usar escucha delegada para admitir elementos creados dinámicamente.
5. Reenviar el formulario original solamente después de la confirmación.
6. Mantener intactos CSRF, método HTTP simulado, validaciones y rutas Laravel.

## Requisitos de calidad

- Eliminar cualquier `window.confirm()` reemplazado por este patrón.
- Probar confirmación, cancelación, fondo, `Escape`, foco y doble clic.
- Comprobar que el diálogo no desborde pantallas pequeñas.
- Verificar que las operaciones protegidas permanezcan deshabilitadas.
- Ejecutar la compilación frontend y las pruebas relacionadas antes de entregar.
