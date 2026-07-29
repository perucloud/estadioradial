# Estación Radial

Portal autoadministrable de noticias, programación y transmisión de radio y
video en línea.

## Estado

Los hitos de acceso, gestión editorial y configuración de portada están
implementados. La descarga original realizada con HTTrack se conserva
localmente en `oldweb/` como referencia y no forma parte de la aplicación ni
del despliegue.

El alcance y las decisiones técnicas están documentados en:

- [`docs/PLAN.md`](docs/PLAN.md)
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)
- [`docs/ADMIN_PLAN.md`](docs/ADMIN_PLAN.md)
- [`docs/ADMIN_SETUP.md`](docs/ADMIN_SETUP.md)
- [`docs/EDITORIAL_SETUP.md`](docs/EDITORIAL_SETUP.md)
- [`docs/EDITORIAL.md`](docs/EDITORIAL.md)

## Entorno local

El origen canónico de desarrollo es:

```text
https://estacionradial.test
```

Laragon debe apuntar el virtual host a `C:/laragon/www/estacionradial/public`.
El acceso HTTP redirige a HTTPS. No es necesario ejecutar `php artisan serve`
para el trabajo habitual.

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

La migración carga localmente el catálogo nominal de países y la jerarquía
UBIGEO completa del Perú. Puede sincronizarse nuevamente, sin borrar relaciones
existentes, con `php artisan locations:import-catalogs`.

La configuración inicial utiliza SQLite para facilitar el desarrollo. En
producción se utilizará MySQL o MariaDB. `APP_URL`, `ASSET_URL`,
`SESSION_DOMAIN` y las opciones HTTPS del `.env` deben reemplazarse por los
valores del dominio real al desplegar.

## Tecnologías

- Laravel
- Blade
- Tailwind CSS
- Vite
- SQLite en desarrollo
- MySQL/MariaDB en producción
