------------------------------------------------------------------------------------
-- PA4 - Script actualizado (SQL Server). Base: PA3web.sql
-- CAMBIOS respecto a la evaluación anterior:
--   1) TBL_USUARIO.CLAVE pasa de VARCHAR(50) a VARCHAR(255): ahora guarda el HASH bcrypt
--      generado con password_hash() de PHP, ya no la clave en texto plano.
--   2) Los 5 usuarios de prueba se insertan con su hash (las claves de prueba de la
--      evaluación anterior siguen siendo válidas para password_verify()).
-- El resto del modelo (tablas, relaciones, restricciones, procedimiento) NO cambia.
-- Para ejecutar la API con SQL Server: DB_DRIVER=sqlsrv en el archivo .env
------------------------------------------------------------------------------------
USE master
GO
IF DB_ID('PA3_PIXELPULSE') IS NOT NULL DROP DATABASE PA3_PIXELPULSE
GO

CREATE DATABASE PA3_PIXELPULSE
GO

USE PA3_PIXELPULSE
GO

------------------------------------------------------------------------------------
CREATE TABLE TBL_USUARIO (
    IDUSUARIO INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
    NOMBRES VARCHAR(80) NOT NULL,
    APELLIDOS VARCHAR(80) NOT NULL,
    CORREO VARCHAR(100) UNIQUE NOT NULL,
    CLAVE VARCHAR(255) NOT NULL,
    ROL VARCHAR(20) NOT NULL,
    ESTADO BIT NOT NULL DEFAULT 1,
    CONSTRAINT CK_USUARIO_ROL CHECK (ROL IN ('Administrador', 'Editor')) )

------------------------------------------------------------------------------------
CREATE TABLE TBL_GENERO (
    IDGENERO INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
    NOMBREGENERO VARCHAR(50) UNIQUE NOT NULL,
    DESCRIPCION VARCHAR(200) NULL )

------------------------------------------------------------------------------------
CREATE TABLE TBL_PLATAFORMA (
    IDPLATAFORMA INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
    NOMBREPLATAFORMA VARCHAR(50) UNIQUE NOT NULL,
    TIPO VARCHAR(20) NOT NULL,
    FABRICANTE VARCHAR(50) NULL,
    CONSTRAINT CK_PLATAFORMA_TIPO CHECK (TIPO IN ('PC', 'Consola', 'Portatil', 'Movil')) )

------------------------------------------------------------------------------------
CREATE TABLE TBL_JUEGO (
    IDJUEGO INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
    TITULO VARCHAR(120) NOT NULL,
    DESARROLLADOR VARCHAR(100) NULL,
    FECHALANZAMIENTO DATE NULL,
    PRECIO DECIMAL(8,2) NOT NULL DEFAULT 0,
    ESTADO BIT NOT NULL DEFAULT 1,
    IDGENERO INT NOT NULL,
    IDPLATAFORMA INT NOT NULL,
    CONSTRAINT FK_JUEGO_GENERO FOREIGN KEY (IDGENERO) REFERENCES TBL_GENERO(IDGENERO),
    CONSTRAINT FK_JUEGO_PLATAFORMA FOREIGN KEY (IDPLATAFORMA) REFERENCES TBL_PLATAFORMA(IDPLATAFORMA),
    CONSTRAINT CK_JUEGO_PRECIO CHECK (PRECIO >= 0) )

------------------------------------------------------------------------------------
CREATE TABLE TBL_RESENA (
    IDRESENA INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
    TITULO VARCHAR(120) NOT NULL,
    CONTENIDO VARCHAR(500) NOT NULL,
    PUNTUACION DECIMAL(3,1) NOT NULL,
    ESTADO VARCHAR(15) NOT NULL DEFAULT 'Publicada',
    FECHA DATE NOT NULL DEFAULT GETDATE(),
    IDJUEGO INT NOT NULL,
    IDUSUARIO INT NOT NULL,
    CONSTRAINT FK_RESENA_JUEGO FOREIGN KEY (IDJUEGO) REFERENCES TBL_JUEGO(IDJUEGO),
    CONSTRAINT FK_RESENA_USUARIO FOREIGN KEY (IDUSUARIO) REFERENCES TBL_USUARIO(IDUSUARIO),
    CONSTRAINT CK_RESENA_PUNTUACION CHECK (PUNTUACION BETWEEN 0 AND 10),
    CONSTRAINT CK_RESENA_ESTADO CHECK (ESTADO IN ('Borrador', 'Publicada')) )

------------------------------------------------------------------------------------
CREATE TABLE TBL_NOTICIA (
    IDNOTICIA INT IDENTITY(1,1) PRIMARY KEY NOT NULL,
    TITULO VARCHAR(150) NOT NULL,
    RESUMEN VARCHAR(300) NOT NULL,
    CATEGORIA VARCHAR(30) NOT NULL,
    FECHA DATE NOT NULL DEFAULT GETDATE(),
    PUBLICADA BIT NOT NULL DEFAULT 1,
    IDUSUARIO INT NOT NULL,
    IDJUEGO INT NULL,
    CONSTRAINT FK_NOTICIA_USUARIO FOREIGN KEY (IDUSUARIO) REFERENCES TBL_USUARIO(IDUSUARIO),
    CONSTRAINT FK_NOTICIA_JUEGO FOREIGN KEY (IDJUEGO) REFERENCES TBL_JUEGO(IDJUEGO),
    CONSTRAINT CK_NOTICIA_CATEGORIA CHECK (CATEGORIA IN ('Lanzamientos', 'Industria', 'Indie', 'Movil', 'Eventos')) )
GO

------------------------------------------------------------------------------------
-- ///ÍNDICES (para buscar más rápido por juego, género y fecha)////
------------------------------------------------------------------------------------
CREATE INDEX IX_JUEGO_IDGENERO ON TBL_JUEGO (IDGENERO)
CREATE INDEX IX_RESENA_IDJUEGO ON TBL_RESENA (IDJUEGO)
CREATE INDEX IX_NOTICIA_FECHA ON TBL_NOTICIA (FECHA)
GO

------------------------------------------------------------------------------------
-- PROCEDIMIENTO ALMACENADO: registrar una reseña nueva
-- Valida que la puntuación esté entre 0 y 10 antes de guardar.
------------------------------------------------------------------------------------
CREATE PROCEDURE SP_REGISTRAR_RESENA
    @IDJUEGO INT,
    @IDUSUARIO INT,
    @TITULO VARCHAR(120),
    @CONTENIDO VARCHAR(500),
    @PUNTUACION DECIMAL(3,1)
AS
BEGIN
    IF @PUNTUACION < 0 OR @PUNTUACION > 10
    BEGIN
        PRINT 'Error: la puntuación debe estar entre 0 y 10.'
        RETURN
    END

    INSERT INTO TBL_RESENA (TITULO, CONTENIDO, PUNTUACION, IDJUEGO, IDUSUARIO)
    VALUES (@TITULO, @CONTENIDO, @PUNTUACION, @IDJUEGO, @IDUSUARIO)

    PRINT 'Reseña registrada correctamente.'
END
GO

------------------------------------------------------------------------------------
-- DATOS DE PRUEBA (máximo 10 por tabla)
-- Los IDs se generan solos en orden (1, 2, 3...), por eso las FK usan esos números.
------------------------------------------------------------------------------------

-- USUARIOS (5)
INSERT INTO TBL_USUARIO (NOMBRES, APELLIDOS, CORREO, CLAVE, ROL, ESTADO) VALUES
('Carlos', 'Mendoza Ruiz', 'carlos.mendoza@pixelpulse.com', '$2y$12$q54g3SL/F/UoWp.4IaDoBO.EpIrIpylsogYTOnOedD6wsFxjCXg.G', 'Administrador', 1),
('Lucía',  'Paredes Soto', 'lucia.paredes@pixelpulse.com',  '$2y$12$ee5909W0Iz2Ixbem1rQO4OimVbm9uKIVPBcnq81fZ6BOUVq/8CEWm', 'Editor', 1),
('Diego',  'Salazar Vega', 'diego.salazar@pixelpulse.com',  '$2y$12$x31uOYS9QkrbPAr4crJqzenu2BQW0C/vvZfQWEA1Fm/7igM7uge2C', 'Editor', 1),
('Andrea', 'Quispe León',  'andrea.quispe@pixelpulse.com',  '$2y$12$v2csM2eDStXNIt8TO3ZYZ.vsfaJmPp.sOk18cqq2YA6FxGosT1Tmi', 'Editor', 1),
('Marco',  'Rojas Díaz',   'marco.rojas@pixelpulse.com',    '$2y$12$ptbye/W1.SkU7dVzJHWECOjIXRohrjR9MwVa2PXqmQWICt8bUmZcC', 'Editor', 0)

-- GÉNEROS (7)
INSERT INTO TBL_GENERO (NOMBREGENERO, DESCRIPCION) VALUES
('Acción',     'Combate y reflejos como eje del juego.'),
('Aventura',   'Exploración e historia por encima del combate.'),
('Rol',        'Progresión de personajes y decisiones del jugador.'),
('Carreras',   'Competencias de velocidad con vehículos.'),
('Puzles',     'Retos de lógica y resolución de problemas.'),
('Estrategia', 'Planificación y gestión de recursos.'),
('Deportes',   'Simuladores y juegos de competencia deportiva.')

-- PLATAFORMAS (6)
INSERT INTO TBL_PLATAFORMA (NOMBREPLATAFORMA, TIPO, FABRICANTE) VALUES
('PC',               'PC',       NULL),
('PlayStation 5',    'Consola',  'Sony'),
('Xbox Series X|S',  'Consola',  'Microsoft'),
('Nintendo Switch',  'Portatil', 'Nintendo'),
('Android',          'Movil',    'Google'),
('iOS',              'Movil',    'Apple')

-- JUEGOS (10): TITULO, DESARROLLADOR, FECHALANZAMIENTO, PRECIO, ESTADO, IDGENERO, IDPLATAFORMA
INSERT INTO TBL_JUEGO (TITULO, DESARROLLADOR, FECHALANZAMIENTO, PRECIO, ESTADO, IDGENERO, IDPLATAFORMA) VALUES
('Nebula Drift',      'Orbit Studio',          '2026-03-12', 29.99, 1, 4, 1),
('Hollow Crown',      'Crownfall Games',       '2026-06-18', 59.99, 1, 3, 2),
('Ashen Circuit',     'Tinker Pixel',          '2026-04-02',  0.00, 1, 5, 5),
('Starforge Tactics', 'Vanta Works',           '2026-05-21', 39.90, 1, 6, 1),
('Echoes of Alder',   'Moss Lantern Studio',   '2026-02-14', 34.99, 1, 2, 4),
('Iron Meridian',     'Blackreef Interactive', '2026-07-09', 49.99, 1, 1, 3),
('Kickoff Legends',   'Pitchside Labs',        '2026-08-28', 54.99, 1, 7, 2),
('Pocket Rhythm',     'Beat Garden',           '2026-09-04',  0.00, 1, 5, 6),
('Dune Runner',       'Sandstorm Mobile',      '2026-01-23',  4.99, 1, 4, 5),
('Lanternfall',       'Moss Lantern Studio',   '2026-09-25', 24.99, 1, 2, 1)

-- RESEÑAS (8 aquí + 1 con el procedimiento): TITULO, CONTENIDO, PUNTUACION, ESTADO, FECHA, IDJUEGO, IDUSUARIO
INSERT INTO TBL_RESENA (TITULO, CONTENIDO, PUNTUACION, ESTADO, FECHA, IDJUEGO, IDUSUARIO) VALUES
('Velocidad pura',      'Controles precisos y una banda sonora que acompaña cada curva.',         9.2, 'Publicada', '2026-10-02', 1, 2),
('Un mundo enorme',     'Combates exigentes y un mundo extenso, con tramos de ritmo irregular.',  8.6, 'Publicada', '2026-10-03', 2, 3),
('Ideas originales',    'Buen diseño de niveles, aunque se vuelve repetitivo hacia el final.',    7.9, 'Publicada', '2026-10-04', 3, 4),
('Estrategia sólida',   'Mapas variados y una curva de dificultad bien ajustada.',                8.1, 'Publicada', '2026-10-05', 4, 2),
('Una aventura cálida', 'Dirección de arte cuidada y una historia que se disfruta sin prisa.',    8.8, 'Publicada', '2026-10-06', 5, 3),
('Acción sin pausa',    'Combates intensos, pero el apartado técnico aún necesita pulirse.',      7.4, 'Publicada', '2026-10-07', 6, 4),
('Buen arranque',       'Reseña en revisión: faltan probar los modos en línea.',                  6.9, 'Borrador',  '2026-10-08', 7, 2),
('Segunda opinión',     'Excelente sensación de velocidad, con pocos circuitos al inicio.',       9.0, 'Publicada', '2026-10-09', 1, 3)

-- NOTICIAS (8): TITULO, RESUMEN, CATEGORIA, FECHA, PUBLICADA, IDUSUARIO, IDJUEGO
INSERT INTO TBL_NOTICIA (TITULO, RESUMEN, CATEGORIA, FECHA, PUBLICADA, IDUSUARIO, IDJUEGO) VALUES
('Hollow Crown suma un modo cooperativo para cuatro jugadores', 'El estudio detalló las novedades de su juego de acción y rol, con progresión compartida.', 'Lanzamientos', '2026-10-08', 1, 2, 2),
('Ashen Circuit estrena nuevos niveles de puzles',              'La actualización añade retos nuevos y mejora el rendimiento en equipos modestos.',      'Indie',         '2026-10-06', 1, 3, 3),
('Cinco juegos independientes que no debes perderte',           'Una selección de títulos pequeños con ideas muy originales.',                           'Indie',         '2026-10-02', 1, 4, NULL),
('Pocket Rhythm llega con canciones nuevas cada semana',        'El juego de ritmo para iOS incorporará un calendario de estrenos musicales.',           'Movil',         '2026-10-04', 1, 2, 8),
('Nebula Drift recibe una actualización con circuitos nuevos',  'Se suman tres pistas y un modo contrarreloj para competir con amigos.',                 'Lanzamientos', '2026-10-09', 1, 3, 1),
('Los estudios apuestan por lanzamientos escalonados',          'Cada vez más equipos publican sus juegos por etapas para recibir comentarios.',         'Industria',     '2026-09-30', 1, 1, NULL),
('Lanternfall confirma su llegada a PC',                        'La aventura de exploración ya tiene versión disponible para ordenadores.',              'Lanzamientos', '2026-09-25', 1, 4, 10),
('Se prepara una jornada gamer local',                          'Nota en borrador: faltan confirmar fecha y lugar del evento.',                          'Eventos',       '2026-10-10', 0, 3, NULL)
GO

------------------------------------------------------------------------------------
-- PRUEBA DEL PROCEDIMIENTO (ver el resultado en la pestaña "Mensajes")
------------------------------------------------------------------------------------
-- 1) Reseña válida: se guarda (es la reseña número 9)
EXEC SP_REGISTRAR_RESENA @IDJUEGO = 8, @IDUSUARIO = 4, @TITULO = 'Ritmo de bolsillo',
     @CONTENIDO = 'Canciones pegajosas y controles táctiles muy bien resueltos.', @PUNTUACION = 8.3

-- 2) Puntuación inválida: muestra el error y NO guarda nada
EXEC SP_REGISTRAR_RESENA @IDJUEGO = 8, @IDUSUARIO = 3, @TITULO = 'Prueba',
     @CONTENIDO = 'Esta reseña no debe guardarse.', @PUNTUACION = 15
GO

------------------------------------------------------------------------------------
-- COMPROBACIONES
------------------------------------------------------------------------------------
-- Cantidad de registros (esperado: 5, 7, 6, 10, 9, 8)
SELECT 'TBL_USUARIO' AS TABLA, COUNT(*) AS REGISTROS FROM TBL_USUARIO
UNION ALL SELECT 'TBL_GENERO', COUNT(*) FROM TBL_GENERO
UNION ALL SELECT 'TBL_PLATAFORMA', COUNT(*) FROM TBL_PLATAFORMA
UNION ALL SELECT 'TBL_JUEGO', COUNT(*) FROM TBL_JUEGO
UNION ALL SELECT 'TBL_RESENA', COUNT(*) FROM TBL_RESENA
UNION ALL SELECT 'TBL_NOTICIA', COUNT(*) FROM TBL_NOTICIA

-- Reseñas publicadas con su juego, plataforma, género y autor
SELECT R.IDRESENA, J.TITULO AS JUEGO, P.NOMBREPLATAFORMA AS PLATAFORMA,
       G.NOMBREGENERO AS GENERO, R.PUNTUACION, U.NOMBRES + ' ' + U.APELLIDOS AS AUTOR
FROM TBL_RESENA R
INNER JOIN TBL_JUEGO J ON J.IDJUEGO = R.IDJUEGO
INNER JOIN TBL_PLATAFORMA P ON P.IDPLATAFORMA = J.IDPLATAFORMA
INNER JOIN TBL_GENERO G ON G.IDGENERO = J.IDGENERO
INNER JOIN TBL_USUARIO U ON U.IDUSUARIO = R.IDUSUARIO
WHERE R.ESTADO = 'Publicada'
ORDER BY R.PUNTUACION DESC
GO
