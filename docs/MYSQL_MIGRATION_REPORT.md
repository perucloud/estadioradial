# Informe de migración de SQLite a MySQL

Fecha: 30 de julio de 2026  
Proyecto: Estación Radial  
Base destino: `estacionradial`

## Resultado

La aplicación quedó conectada a MySQL 8.4.3 mediante la conexión `mysql`. La base
`estacionradial` usa `utf8mb4` y `utf8mb4_unicode_ci`; sus 28 tablas usan InnoDB.
El archivo SQLite original no fue modificado ni eliminado.

## Respaldos

Directorio de reversión:

`storage/app/private/migration-backups/sqlite-to-mysql-20260730-134637`

Contenido:

- `database.sqlite`: copia verificada mediante SHA-256.
- `.env.sqlite`: configuración SQLite previa, verificada mediante SHA-256.

## Conteos verificados

| Tabla | SQLite | MySQL |
|---|---:|---:|
| activity_logs | 55 | 55 |
| advertisements | 4 | 4 |
| cache | 1 | 1 |
| cache_locks | 0 | 0 |
| categories | 14 | 14 |
| failed_jobs | 0 | 0 |
| job_batches | 0 | 0 |
| jobs | 0 | 0 |
| locations | 2327 | 2327 |
| media | 25 | 25 |
| media_post | 0 | 0 |
| migrations | 24 | 24 |
| password_reset_tokens | 0 | 0 |
| permission_role | 39 | 39 |
| permission_user | 0 | 0 |
| permissions | 22 | 22 |
| portal_settings | 8 | 8 |
| post_tag | 82 | 82 |
| posts | 40 | 40 |
| program_user | 0 | 0 |
| programs | 4 | 4 |
| role_user | 1 | 1 |
| roles | 4 | 4 |
| schedules | 28 | 28 |
| sessions | 2 | 2 |
| streams | 2 | 2 |
| tags | 59 | 59 |
| users | 1 | 1 |

La comparación se realizó inmediatamente después de la transferencia. La limpieza
posterior de cachés vació únicamente la tabla técnica `cache` de MySQL.

## Validaciones

- 28 tablas equivalentes.
- 24 migraciones aplicadas.
- 26 claves foráneas inspeccionadas.
- Sin relaciones huérfanas.
- Sin IDs faltantes o adicionales.
- Hash de contraseña idéntico en SQLite y MySQL.
- 24 archivos multimedia activos presentes; un registro multimedia eliminado
  lógicamente ya no conserva su archivo, situación preexistente y esperada.
- Compilación Vite correcta.
- Suite automatizada ejecutada con SQLite en memoria para no destruir MySQL.
- Prueba funcional MySQL: login transaccional, frontend, noticias, búsqueda,
  paginación, dashboard, CKEditor, categorías, ubicaciones y multimedia.

Livewire no está instalado en este proyecto. La interfaz utiliza Blade, Alpine.js,
CKEditor 5 y Vite. Se conserva el atributo `wire:ignore` existente, pero no hay
componentes Livewire que probar.

La contraseña documentada anteriormente como `satipo123` ya no coincide con el
hash que contenía SQLite antes de migrar. El hash vigente fue preservado sin
restablecer ni modificar la credencial.

## Comando de migración

Simulación:

```bash
php artisan db:migrate-sqlite-to-mysql --dry-run
```

Ejecución:

```bash
php artisan db:migrate-sqlite-to-mysql --execute --chunk=500
```

El comando:

- exige escoger exactamente `--dry-run` o `--execute`;
- valida que el origen sea SQLite y el destino MySQL/MariaDB;
- exige tablas equivalentes;
- aborta si el destino contiene datos de negocio;
- copia por lotes dentro de una transacción;
- conserva valores crudos, IDs, fechas, JSON y valores nulos;
- compara conteos e IDs;
- detecta relaciones huérfanas;
- reactiva siempre las claves foráneas.

## Reversión inmediata

Para regresar a SQLite se puede restaurar el `.env` respaldado desde:

`storage/app/private/migration-backups/sqlite-to-mysql-20260730-134637/.env.sqlite`

Después se debe ejecutar:

```bash
php artisan optimize:clear
```

El SQLite original continúa en `database/database.sqlite`.
