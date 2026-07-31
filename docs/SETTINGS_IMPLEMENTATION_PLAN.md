# Plan de implementación: Configurar y Ajustes

## Objetivo

Convertir los menús **Configurar** y **Ajustes** en módulos administrativos
reales, seguros, responsivos y reutilizables. Todos los valores se almacenan en
`portal_settings`; los secretos se cifran y los respaldos permanecen fuera de
`public`.

## Principios

- UTF-8 y textos en español.
- Formularios con validación, mensajes claros y registro de actividad.
- Diseño adaptable a móvil, tableta y escritorio.
- Genio Modal para confirmaciones y selectores; Modal Delete para eliminación
  irreversible.
- Ningún secreto SMTP se muestra ni se registra en texto plano.
- El mantenimiento no bloquea el acceso administrativo.
- Los respaldos se crean en `storage/app/private/backups`.

## Hitos

1. **Base modular**
   - Rutas, navegación activa, catálogo de secciones y almacenamiento común.
   - Páginas profesionales para Configurar y Ajustes.
2. **Identidad y contacto**
   - Logo, nombre, slogan, frecuencia, dirección, teléfono, WhatsApp y correo.
3. **Redes sociales**
   - Facebook, X, TikTok, Instagram y YouTube con validación de URL.
4. **Apariencia y SEO**
   - Paleta pública mediante variables CSS.
   - Título, descripción, palabras clave, indexación y metadatos Open Graph.
5. **Regionalización**
   - Idioma, zona horaria y formatos de fecha/hora aplicados en ejecución.
6. **Correo SMTP**
   - Configuración cifrada, contraseña preservada al editar y envío de prueba.
7. **Operaciones**
   - Estado/limpieza de caché, mantenimiento público y respaldos descargables.
8. **Seguridad**
   - CAPTCHA, intentos, bloqueo, duración de sesión y política de contraseñas.

## Criterios de cierre

- Todos los submenús son enlaces funcionales.
- Los cambios públicos se reflejan sin editar código.
- Acciones sensibles requieren confirmación.
- Las pruebas automatizadas, compilación Vite y auditoría de rutas finalizan
  correctamente.
- Los cambios se confirman en Git y se envían al repositorio remoto.
