# ChecaFlow — reglas para agentes

ChecaFlow es un SaaS B2B multi-tenant de asistencia (ciclo de 4 marcas). El backend es Laravel 13 / PHP 8.4 en Docker. La app móvil React Native todavía no existe.

1. Este repositorio es la fuente de verdad. El código reciente que cumple estas reglas gana sobre un patrón genérico de Laravel.
2. No copies dominio ni módulos de otros repos.
3. No inventes arquitectura, columnas, tablas ni módulos.
4. No acomplejes. El camino más corto que cumple el requisito.
5. Prohibido `else` / `elseif`. Usa guard clauses, early return y polimorfismo.
6. SOLID sin capas extra: una clase, una responsabilidad. Extiende con una clase nueva. El controller no posee negocio.
7. No crees repositorios Eloquent “por SOLID”. La persistencia concreta entra después, detrás de un Port.
8. Domain no importa Eloquent, Facades ni clases de `Illuminate`.
9. Application depende de Ports (interfaces), no de modelos Eloquent.
10. Las clases de Application se nombran verbo + sustantivo. Nunca uses el sufijo `UseCase`.
11. Código, tablas, columnas, enums, excepciones y comentarios técnicos van en inglés claro. Sin abreviaturas crípticas.
12. Toda operación de negocio exige `company_id`. Un usuario de empresa no puede leer datos de otra.
13. `super_admin` es el único rol con `company_id` nulo. El resto de roles exigen empresa. Lo refuerza el CHECK `users_role_company_consistency`.
14. Búsqueda y orden usan DTOs/criterios tipados y una lista blanca de columnas. Nunca un string arbitrario del cliente.
15. Antes de implementar: entiende el pedido → identifica el feature → lee la regla de ese módulo → busca un equivalente en este repo → cambio mínimo → test → actualiza `AGENTS.md`, la regla y `docs/AI_*` solo si cambió arquitectura, dominio, colas, API o permisos.
16. Features previstos: `Auth`, `Companies`, `Branches`, `Schedules`, `Employees`, `Attendance`, `Reports`.
17. Cada feature vive en `app/Features/{Feature}/Domain` y `app/Features/{Feature}/Application`.
18. HTTP de páginas irá en `app/Http`. Aún no hay controladores de negocio ni FormRequests.
19. Migraciones en `database/migrations`. No corras `migrate:fresh` ni borres datos sin petición explícita.
20. No uses `env()` dentro de `app/`. Lee configuración con `config()`.
21. No expongas secretos ni copies valores de `.env` a docs o reglas.
22. No dejes `// TODO` ni métodos vacíos “por si acaso”.
23. El ciclo de asistencia es `clock_in` → `lunch_out` → `lunch_in` → `clock_out`.
24. `RegisterPunch` valida empresa, secuencia, geocerca y luego llama a `CalculateDailyAttendance`.
25. `mobile/` todavía no está creado. Cuando exista, cada feature móvil tendrá `domain/` y `application/`, con acciones nombradas solo con verbo.
26. Node no está instalado en esta máquina. No inicies React Native hasta que el entorno exista.
27. Artisan, Composer y tests corren dentro de `php-checa` (`make up`, `make enter`, `make test`). No ejecutes `migrate:fresh` ni borres volúmenes de Docker sin petición explícita.
28. Laravel trajo `cache`, `jobs`, `sessions` y `password_reset_tokens`. No los reemplaces.
29. Índices y `onDelete` están definidos en las migraciones. No los cambies de paso.
30. Legacy que viole estas reglas no es plantilla. Hoy el único modelo Eloquent es `App\Models\User`, alineado a la tabla `users`.
