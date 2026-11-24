# PixelPulse API (PHP + MVC)

API REST para **PixelPulse**, un portal de reseñas y noticias de videojuegos.
Amplía la base de datos `PA3_PIXELPULSE` con **6 módulos CRUD** (uno por tabla), organizados en
**Modelos, Controladores y Rutas**, tomando como referencia el proyecto base del docente
(`3657_PAGINA_WEB`).

## Estructura

```
pixelpulse-api/
├── public/              Punto de entrada (index.php) y .htaccess
├── config/              Database.php  → conexión PDO (lee el .env)
├── core/                Router, Request, Response, Validator, Env
├── routes/api.php       Rutas de los 6 módulos
├── app/
│   ├── Controllers/     Reciben la petición, validan y responden
│   ├── Models/          Ejecutan las consultas SQL (PDO + prepare)
│   └── Middleware/      CORS
├── database/            Scripts SQL (SQL Server y MySQL) con datos de prueba
├── docs/                Colección de Postman
├── .env.example         Plantilla de variables de entorno (el .env real NO se sube)
└── composer.json
```

Flujo de una petición: `index.php → Router → Controlador → Modelo → Base de datos → Response (JSON)`.

## Instalación con XAMPP (MySQL)

1. Copia la carpeta del proyecto a `C:\xampp\htdocs\pixelpulse-api`.
2. Abre el **XAMPP Control Panel** y pulsa **Start** en **Apache** y **MySQL**.
3. En `http://localhost/phpmyadmin` importa `database/pixelpulse_mysql.sql`.
4. Copia `.env.example` como `.env` (en la raíz del proyecto). Con XAMPP los valores por defecto sirven:
   `DB_DRIVER=mysql`, `DB_USER=root`, `DB_PASS=` (vacía).
5. Prueba en el navegador: `http://localhost/pixelpulse-api/public/` → debe responder
   `"API de PixelPulse funcionando"`.

> No hace falta ejecutar `composer install`: si no existe la carpeta `vendor/`, el proyecto usa un
> autoload propio. Si quieres usar Composer, ejecuta `composer dump-autoload`.

### Variante con SQL Server

1. Ejecuta `database/pixelpulse_sqlserver.sql` en SQL Server Management Studio.
2. Instala el driver **`pdo_sqlsrv`** de Microsoft para tu versión de PHP y actívalo en `php.ini`.
3. En el `.env`: `DB_DRIVER=sqlsrv`, `DB_HOST=localhost\SQLEXPRESS` (o tu instancia), `DB_PORT=` vacío,
   y usuario/clave de SQL Server (déjalos vacíos si usas autenticación de Windows).

## Endpoints

Cada módulo expone las mismas 5 operaciones. URL base: `http://localhost/pixelpulse-api/public`

| Método | Ruta | Acción | Código OK |
|---|---|---|---|
| GET | `/api/{modulo}` | Listar | 200 |
| GET | `/api/{modulo}/{id}` | Consultar por ID | 200 |
| POST | `/api/{modulo}` | Crear | 201 |
| PUT | `/api/{modulo}/{id}` | Actualizar | 200 |
| DELETE | `/api/{modulo}/{id}` | Eliminar | 200 |

Módulos: `usuarios`, `generos`, `plataformas`, `juegos`, `resenas`, `noticias` (6 tablas → 6 módulos, 30 endpoints).

### Formato de respuesta

```json
{ "success": true,  "message": "Género registrado correctamente", "data": { "id_genero": 8 } }
{ "success": false, "message": "El nombre es obligatorio (máximo 50 caracteres)" }
```

### Códigos HTTP

| Código | Cuándo |
|---|---|
| 200 / 201 | Operación correcta / registro creado |
| 400 | Datos inválidos, campos faltantes, JSON mal formado, clave foránea inexistente |
| 404 | El registro (o la ruta) no existe |
| 405 | La ruta existe pero con otro método HTTP |
| 409 | Conflicto: correo/nombre duplicado, o eliminar algo que otros registros usan |
| 500 | Error interno (mensaje genérico; el detalle va al log del servidor) |

## Campos por módulo (cuerpo JSON de POST / PUT)

| Módulo | Campos (obligatorios en negrita) |
|---|---|
| usuarios | **nombres**, **apellidos**, **correo**, **clave** (solo al crear; opcional al actualizar), **rol** (`Administrador` / `Editor`), estado |
| generos | **nombre**, descripcion |
| plataformas | **nombre**, **tipo** (`PC` / `Consola` / `Portatil` / `Movil`), fabricante |
| juegos | **titulo**, **precio** (≥ 0, el 0 es válido), **id_genero**, **id_plataforma**, desarrollador, fecha_lanzamiento (`AAAA-MM-DD`), estado |
| resenas | **titulo**, **contenido**, **puntuacion** (0 a 10), **id_juego**, **id_usuario**, estado (`Borrador` / `Publicada`), fecha |
| noticias | **titulo**, **resumen**, **categoria** (`Lanzamientos` / `Industria` / `Indie` / `Movil` / `Eventos`), **id_usuario**, id_juego (opcional), publicada, fecha |

## Decisiones de diseño

- **Variables de entorno:** los datos de conexión viven en `.env`, que está en `.gitignore`. Solo se sube `.env.example`.
- **Validaciones** reutilizables en `core/Validator.php`; cada controlador valida tipos, longitudes máximas (las mismas de la tabla), listas permitidas (las mismas de los `CHECK`) y que las claves foráneas existan.
- **Eliminación lógica** (`ESTADO = 0`) en `usuarios` y `juegos`, porque tienen reseñas y noticias asociadas.
- **Eliminación física** en `reseñas` y `noticias` (nadie depende de ellas).
- **Géneros y plataformas** no se pueden eliminar si hay juegos que los usan (responde 409 con un mensaje claro).
- **Seguridad:** consultas con `prepare()` (evita inyección SQL); la clave se guarda con `password_hash()` y **nunca** se devuelve en las respuestas; los errores internos no exponen datos sensibles.
- El **precio 0** es válido (hay juegos gratuitos), por eso no se valida con `empty()`.

## Pruebas

Importa `docs/PixelPulse_API.postman_collection.json` en Postman y ejecútala con **Collection Runner**.
Incluye el CRUD completo de cada módulo y los casos de error (400, 404, 405, 409), con pruebas automáticas
de código de estado. La variable `baseUrl` se puede cambiar si tu carpeta tiene otro nombre.

## Subir a GitHub

```bash
git init
git add .
git commit -m "API MVC PixelPulse"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/pixelpulse-api.git
git push -u origin main
```

Si el repositorio es privado, compártelo con el usuario `leyngab-isil`.
Antes de subir, comprueba que `.env` **no** aparece en `git status`.
