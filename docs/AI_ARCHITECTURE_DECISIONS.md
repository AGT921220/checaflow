# Decisiones de arquitectura

## ADR-001 — Laravel 13 sobre un repositorio vacío

- Estado: aceptada
- Contexto: `checaflow` solo tenía el commit inicial y `.gitignore`. No había `Makefile`, backend ni `mobile/`.
- Decisión: se instaló Laravel 13.11 (framework 13.43) dentro del repo, conservando `.git`. No se creó la app React Native.
- Consecuencia: las migraciones corren con Artisan. La Fase 3 necesita Node y un proyecto React Native nuevo.

## ADR-002 — Hexagonal por feature, solo Domain y Application al inicio

- Estado: aceptada
- Contexto: el producto debe crecer de asistencia hacia RH sin un `app/Services` plano.
- Decisión: `app/Features/{Feature}/Domain` y `Application`. Los casos se nombran verbo + sustantivo, sin sufijo `UseCase`. Domain no depende del framework. Los adaptadores Eloquent, controladores y FormRequests quedan para cuando se pidan.
- Consecuencia: Fase 1 no creó esas carpetas vacías.

## ADR-003 — `users.company_id` con restrict y CHECK de rol

- Estado: aceptada
- Contexto: `company_id` es nulo solo para `super_admin`. Un `ON DELETE SET NULL` dejaría empleados sin tenant y fuera de los filtros.
- Decisión: `restrictOnDelete`. Además, en MySQL, MariaDB y PostgreSQL, el CHECK `users_role_company_consistency` obliga: `super_admin` sin empresa, cualquier otro rol con empresa.
- Consecuencia: no se puede borrar la fila de una empresa que aún tiene usuarios. SQLite no recibe ese CHECK porque esta versión de Laravel no expone `check()` en el schema builder y `ALTER TABLE ... ADD CONSTRAINT` no es portable a SQLite.

## ADR-004 — Índice de marcas no único

- Estado: aceptada
- Contexto: el esquema pide índice `(employee_profile_id, work_date, punch_type)`, y único solo donde lo dice de forma explícita.
- Decisión: el índice no es único. El rechazo de una marca duplicada vive en `RegisterPunch`.
- Consecuencia: dos escrituras concurrentes del mismo `punch_type` pueden colar hasta que esa regla exista en aplicación o se promueva el índice a único.

## ADR-005 — Modelo `User` alineado a la migración

- Estado: aceptada
- Contexto: el esqueleto traía `email_verified_at`. La tabla pedida no lo incluye y sí incluye `company_id`, `role`, `is_active` y soft delete.
- Decisión: se quitó `email_verified_at` del modelo y del factory. El factory crea un `super_admin` sin empresa, que es el único estado válido sin un tenant.
- Consecuencia: no hay verificación de correo del framework hasta que se pida.

## ADR-006 — Docker local con Nginx, PHP-FPM, MySQL y Redis

- Estado: aceptada
- Contexto: el esqueleto quedó en SQLite del host, sin `Makefile` ni Compose.
- Decisión: el entorno de trabajo es `docker-compose.yml`: `nginx-checa`, `php-checa` (PHP 8.4-FPM), `horizon-checa`, `scheduler-checa`, `mysql-checa`, `redis-checa` y `pma-checa`. Staging (`docker-compose-staging.yaml`) no trae MySQL ni phpMyAdmin y usa la red externa `appnetwork`. Artisan se ejecuta con `docker exec php-checa`.
- Consecuencia: `.env` apunta a los hostnames `mysql` y `redis`. Los puertos del host son 8098 (HTTP), 8097 (phpMyAdmin), 4336 (MySQL) y 6386 (Redis). El 8096 ya lo usa otro proyecto. `make restart` solo reinicia contenedores; no borra datos. La red local queda fija en `172.16.60.0/24` porque los pools automáticos de Docker de esta máquina ya no tienen subred libre.
