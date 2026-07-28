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

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

La configuración inicial utiliza SQLite para facilitar el desarrollo. En
producción se utilizará MySQL o MariaDB.

## Tecnologías

- Laravel
- Blade
- Tailwind CSS
- Vite
- SQLite en desarrollo
- MySQL/MariaDB en producción

