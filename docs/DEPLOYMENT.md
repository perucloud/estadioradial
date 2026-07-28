# Despliegue en hosting compartido

Este documento se completará y validará durante la etapa de producción.

## Requisitos previstos

- PHP compatible con la versión de Laravel seleccionada.
- MySQL o MariaDB.
- Extensiones PHP requeridas por Laravel.
- Acceso a cron desde el panel del hosting.
- Certificado HTTPS.
- Dominio apuntando preferentemente a la carpeta `public/`.

## Modalidades

### Document root configurable

La opción recomendada es mantener la aplicación fuera del directorio público y
apuntar el dominio a `public/`.

### cPanel con `public_html`

Si el proveedor no permite cambiar el document root, se preparará una guía para
separar el núcleo de Laravel y publicar solamente el contenido de `public/`.

## Instalación prevista

1. Subir el paquete de producción.
2. Configurar `.env`.
3. Instalar dependencias o utilizar el paquete preparado sin herramientas de
   desarrollo.
4. Generar la clave de aplicación.
5. Ejecutar migraciones y datos iniciales.
6. Crear el enlace de almacenamiento.
7. Configurar cron.
8. Verificar HTTPS, cachés, permisos y streaming.

`oldweb/` no se incluirá en el paquete de producción.

