---
name: genio-modal
description: Aplicar o mantener el patrón visual “Genio Modal”: una ventana modal que nace desde el botón activador mediante un cuello líquido curvo, se expande con morphing SVG Bézier y se contrae al mismo botón al cerrar. Usar cuando el usuario diga “aplica Genio Modal”, “efecto Genio Modal”, “efecto genio” o solicite reutilizar esta animación en un modal, diálogo, calendario o panel flotante.
---

# Genio Modal

Aplicar una transición espacial que muestre con claridad que el modal nace del botón que lo abrió y regresa a él al cerrarse.

## Especificación

- Dibujar la transición con un SVG superpuesto y una figura rellena creada con curvas Bézier.
- Empezar en los límites reales del botón activador.
- Heredar el color del botón activador y conservarlo durante la apertura y el cierre.
- Resolver el color en este orden: variable CSS `--genie-color`, atributo `data-genie-color`, fondo calculado del botón y color de respaldo.
- Generar automáticamente tonos oscuro y claro a partir del color base para el degradado líquido.
- Mantener al inicio un cuello angosto y curvo; ensanchar primero la parte superior y después la inferior.
- Terminar exactamente en los límites del diálogo centrado.
- Usar `420 ms` para abrir y `300 ms` para cerrar.
- Usar una aceleración suave y orgánica; evitar rebotes exagerados.
- Desvanecer y escalar ligeramente el contenido del modal durante la última parte de la apertura.
- Reproducir la transición de forma inversa al cerrar mediante botón, fondo o tecla Escape.
- Bloquear clics duplicados mientras la animación esté activa.

## Integración

1. Localizar el botón activador y el diálogo correspondiente.
2. Medir ambos con `getBoundingClientRect()` después de mostrar el diálogo.
3. Crear la capa SVG en el top layer mediante Popover API cuando esté disponible.
4. Actualizar el atributo `d` de la figura en `requestAnimationFrame`.
5. Conservar un fallback con Web Animations basado en traslación y escala.
6. Restaurar estilos temporales, foco y bloqueo de desplazamiento al finalizar.

En Estación Radial, reutilizar o generalizar antes que duplicar:

- `runGenieMorph()` y `genieFallbackFrames()` en `resources/js/admin-genie-modal.js`.
- `.admin-genie-layer` y sus elementos en `resources/css/admin.css`.

## Requisitos de calidad

- Centrar el modal en escritorio, tablet y móvil.
- Recalcular la geometría en cada apertura; no guardar coordenadas antiguas.
- Respetar `prefers-reduced-motion: reduce` y abrir/cerrar sin morphing en ese caso.
- Mantener navegación por teclado, Escape, foco inicial y atributos ARIA.
- No hacer que el SVG intercepte eventos del puntero.
- Verificar apertura, cierre, fondo, Escape y redimensionamiento.
- Ejecutar la compilación frontend y las pruebas relacionadas antes de entregar.
