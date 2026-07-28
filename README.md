# Estación Radial

Portal autoadministrable de noticias, programación y transmisión de radio y
video en línea.

## Estado

El proyecto se encuentra en su fase inicial. La descarga original realizada con
HTTrack se conserva localmente en `oldweb/` como referencia y no forma parte de
la aplicación ni del despliegue.

El alcance y las decisiones técnicas están documentados en:

- [`docs/PLAN.md`](docs/PLAN.md)
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)

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
