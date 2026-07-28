# Acceso al panel administrativo

## Dirección local

El panel se abre en:

```text
https://estacionradial.test/admin
```

El acceso utiliza el dominio virtual de Laragon. No es necesario ejecutar
`php artisan serve`.

## Preparación inicial

```bash
php artisan migrate --force
php artisan db:seed --class=AdminAccessSeeder --force
php artisan admin:create-superadmin superadmin@gmail.com
```

El último comando solicita nombre, contraseña y confirmación sin guardar la
clave en el historial del repositorio. La cuenta queda obligada a crear una
contraseña nueva durante su primer acceso.

Para automatización segura también se admite una contraseña por entrada
estándar:

```bash
php artisan admin:create-superadmin superadmin@gmail.com \
  --name=Superadministrador \
  --password-stdin
```

La clave debe enviarse por `stdin`; no debe escribirse como argumento, seeder,
migración ni variable versionada.

## Recuperación de contraseña

En desarrollo, `MAIL_MAILER=log` escribe el mensaje de recuperación en
`storage/logs/laravel.log`. En producción se deberá configurar SMTP y verificar
el remitente antes de habilitar el panel al cliente.

Los enlaces de recuperación:

- son temporales;
- se pueden utilizar una sola vez;
- tienen limitación de solicitudes;
- no revelan si el correo consultado existe;
- invalidan las sesiones anteriores al cambiar la contraseña.

## Roles iniciales

- **Superadministrador:** acceso total y creación de todos los roles.
- **Administrador:** recibe módulos delegados y puede crear editores y
  locutores.
- **Editor:** operación editorial según sus permisos.
- **Locutor:** programas y horarios asignados.

La interfaz oculta las opciones no disponibles y el servidor vuelve a validar
cada acción mediante middleware y permisos.

## Comprobación

Antes de entregar cambios del panel:

```bash
vendor/bin/pint --test
php artisan test
npm run build
```
