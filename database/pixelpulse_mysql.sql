-- =====================================================================
-- PA4 - PIXELPULSE - Script para MySQL / MariaDB (XAMPP)
-- Es la conversión de PA3web.sql (SQL Server). El modelo es el mismo:
-- 6 tablas, mismas relaciones y mismas restricciones.
-- Cómo ejecutarlo: phpMyAdmin -> pestaña "Importar", o pestaña "SQL" (pegar y ejecutar).
-- Para la API: DB_DRIVER=mysql en el archivo .env
-- =====================================================================

DROP DATABASE IF EXISTS PA3_PIXELPULSE;

CREATE DATABASE PA3_PIXELPULSE
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE PA3_PIXELPULSE;

-- ---------------------------------------------------------------------
CREATE TABLE TBL_USUARIO (
    IDUSUARIO INT AUTO_INCREMENT PRIMARY KEY,
    NOMBRES   VARCHAR(80)  NOT NULL,
    APELLIDOS VARCHAR(80)  NOT NULL,
    CORREO    VARCHAR(100) NOT NULL UNIQUE,
    CLAVE     VARCHAR(255) NOT NULL,          -- hash bcrypt (60 caracteres)
    ROL       VARCHAR(20)  NOT NULL,
    ESTADO    TINYINT(1)   NOT NULL DEFAULT 1,
    CONSTRAINT CK_USUARIO_ROL CHECK (ROL IN ('Administrador', 'Editor'))
);

CREATE TABLE TBL_GENERO (
    IDGENERO     INT AUTO_INCREMENT PRIMARY KEY,
    NOMBREGENERO VARCHAR(50)  NOT NULL UNIQUE,
    DESCRIPCION  VARCHAR(200) NULL
);

CREATE TABLE TBL_PLATAFORMA (
    IDPLATAFORMA     INT AUTO_INCREMENT PRIMARY KEY,
    NOMBREPLATAFORMA VARCHAR(50) NOT NULL UNIQUE,
    TIPO             VARCHAR(20) NOT NULL,
    FABRICANTE       VARCHAR(50) NULL,
    CONSTRAINT CK_PLATAFORMA_TIPO CHECK (TIPO IN ('PC', 'Consola', 'Portatil', 'Movil'))
);

CREATE TABLE TBL_JUEGO (
    IDJUEGO          INT AUTO_INCREMENT PRIMARY KEY,
    TITULO           VARCHAR(120)  NOT NULL,
    DESARROLLADOR    VARCHAR(100)  NULL,
    FECHALANZAMIENTO DATE          NULL,
    PRECIO           DECIMAL(8,2)  NOT NULL DEFAULT 0,
    ESTADO           TINYINT(1)    NOT NULL DEFAULT 1,
    IDGENERO         INT NOT NULL,
    IDPLATAFORMA     INT NOT NULL,
    CONSTRAINT FK_JUEGO_GENERO     FOREIGN KEY (IDGENERO)     REFERENCES TBL_GENERO(IDGENERO),
    CONSTRAINT FK_JUEGO_PLATAFORMA FOREIGN KEY (IDPLATAFORMA) REFERENCES TBL_PLATAFORMA(IDPLATAFORMA),
    CONSTRAINT CK_JUEGO_PRECIO CHECK (PRECIO >= 0)
);

CREATE TABLE TBL_RESENA (
    IDRESENA   INT AUTO_INCREMENT PRIMARY KEY,
    TITULO     VARCHAR(120)  NOT NULL,
    CONTENIDO  VARCHAR(500)  NOT NULL,
    PUNTUACION DECIMAL(3,1)  NOT NULL,
    ESTADO     VARCHAR(15)   NOT NULL DEFAULT 'Publicada',
    FECHA      DATE          NOT NULL,          -- la API envía la fecha de hoy si no se indica
    IDJUEGO    INT NOT NULL,
    IDUSUARIO  INT NOT NULL,
    CONSTRAINT FK_RESENA_JUEGO   FOREIGN KEY (IDJUEGO)   REFERENCES TBL_JUEGO(IDJUEGO),
    CONSTRAINT FK_RESENA_USUARIO FOREIGN KEY (IDUSUARIO) REFERENCES TBL_USUARIO(IDUSUARIO),
    CONSTRAINT CK_RESENA_PUNTUACION CHECK (PUNTUACION BETWEEN 0 AND 10),
    CONSTRAINT CK_RESENA_ESTADO CHECK (ESTADO IN ('Borrador', 'Publicada'))
);

CREATE TABLE TBL_NOTICIA (
    IDNOTICIA INT AUTO_INCREMENT PRIMARY KEY,
    TITULO    VARCHAR(150) NOT NULL,
    RESUMEN   VARCHAR(300) NOT NULL,
    CATEGORIA VARCHAR(30)  NOT NULL,
    FECHA     DATE         NOT NULL,            -- la API envía la fecha de hoy si no se indica
    PUBLICADA TINYINT(1)   NOT NULL DEFAULT 1,
    IDUSUARIO INT NOT NULL,
    IDJUEGO   INT NULL,
    CONSTRAINT FK_NOTICIA_USUARIO FOREIGN KEY (IDUSUARIO) REFERENCES TBL_USUARIO(IDUSUARIO),
    CONSTRAINT FK_NOTICIA_JUEGO   FOREIGN KEY (IDJUEGO)   REFERENCES TBL_JUEGO(IDJUEGO),
    CONSTRAINT CK_NOTICIA_CATEGORIA CHECK (CATEGORIA IN ('Lanzamientos', 'Industria', 'Indie', 'Movil', 'Eventos'))
);

-- ---------------------------------------------------------------------
-- ÍNDICES (para buscar más rápido por género y por fecha)
-- ---------------------------------------------------------------------
CREATE INDEX IX_JUEGO_IDGENERO ON TBL_JUEGO (IDGENERO);
CREATE INDEX IX_RESENA_IDJUEGO ON TBL_RESENA (IDJUEGO);
CREATE INDEX IX_NOTICIA_FECHA  ON TBL_NOTICIA (FECHA);

-- ---------------------------------------------------------------------
-- PROCEDIMIENTO ALMACENADO: registrar una reseña nueva
-- Valida que la puntuación esté entre 0 y 10. En MySQL no existe PRINT:
-- el error se lanza con SIGNAL y el mensaje de éxito se devuelve con SELECT.
-- ---------------------------------------------------------------------
DELIMITER //

CREATE PROCEDURE SP_REGISTRAR_RESENA(
    IN p_idjuego     INT,
    IN p_idusuario   INT,
    IN p_titulo      VARCHAR(120),
    IN p_contenido   VARCHAR(500),
    IN p_puntuacion  DECIMAL(3,1)
)
BEGIN
    IF p_puntuacion < 0 OR p_puntuacion > 10 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Error: la puntuación debe estar entre 0 y 10.';
    END IF;

    INSERT INTO TBL_RESENA (TITULO, CONTENIDO, PUNTUACION, ESTADO, FECHA, IDJUEGO, IDUSUARIO)
    VALUES (p_titulo, p_contenido, p_puntuacion, 'Publicada', CURDATE(), p_idjuego, p_idusuario);

    SELECT 'Reseña registrada correctamente.' AS mensaje;
END //

DELIMITER ;

-- ---------------------------------------------------------------------
-- DATOS DE PRUEBA
-- ---------------------------------------------------------------------

-- USUARIOS (5): CLAVE guardada con hash bcrypt
INSERT INTO TBL_USUARIO (NOMBRES, APELLIDOS, CORREO, CLAVE, ROL, ESTADO) VALUES
('Carlos', 'Mendoza Ruiz', 'carlos.mendoza@pixelpulse.com', '$2y$12$q54g3SL/F/UoWp.4IaDoBO.EpIrIpylsogYTOnOedD6wsFxjCXg.G', 'Administrador', 1),
('Lucía',  'Paredes Soto', 'lucia.paredes@pixelpulse.com',  '$2y$12$ee5909W0Iz2Ixbem1rQO4OimVbm9uKIVPBcnq81fZ6BOUVq/8CEWm', 'Editor', 1),
('Diego',  'Salazar Vega', 'diego.salazar@pixelpulse.com',  '$2y$12$x31uOYS9QkrbPAr4crJqzenu2BQW0C/vvZfQWEA1Fm/7igM7uge2C', 'Editor', 1),
('Andrea', 'Quispe León',  'andrea.quispe@pixelpulse.com',  '$2y$12$v2csM2eDStXNIt8TO3ZYZ.vsfaJmPp.sOk18cqq2YA6FxGosT1Tmi', 'Editor', 1),
('Marco',  'Rojas Díaz',   'marco.rojas@pixelpulse.com',    '$2y$12$ptbye/W1.SkU7dVzJHWECOjIXRohrjR9MwVa2PXqmQWICt8bUmZcC', 'Editor', 0);

-- GÉNEROS (7)
INSERT INTO TBL_GENERO (NOMBREGENERO, DESCRIPCION) VALUES
('Acción',     'Combate y reflejos como eje del juego.'),
('Aventura',   'Exploración e historia por encima del combate.'),
('Rol',        'Progresión de personajes y decisiones del jugador.'),
('Carreras',   'Competencias de velocidad con vehículos.'),
('Puzles',     'Retos de lógica y resolución de problemas.'),
('Estrategia', 'Planificación y gestión de recursos.'),
('Deportes',   'Simuladores y juegos de competencia deportiva.');

-- PLATAFORMAS (6)
INSERT INTO TBL_PLATAFORMA (NOMBREPLATAFORMA, TIPO, FABRICANTE) VALUES
('PC',               'PC',       NULL),
('PlayStation 5',    'Consola',  'Sony'),
('Xbox Series X|S',  'Consola',  'Microsoft'),
('Nintendo Switch',  'Portatil', 'Nintendo'),
('Android',          'Movil',    'Google'),
('iOS',              'Movil',    'Apple');

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
('Lanternfall',       'Moss Lantern Studio',   '2026-09-25', 24.99, 1, 2, 1);

-- RESEÑAS (8 aquí + 1 con el procedimiento): TITULO, CONTENIDO, PUNTUACION, ESTADO, FECHA, IDJUEGO, IDUSUARIO
INSERT INTO TBL_RESENA (TITULO, CONTENIDO, PUNTUACION, ESTADO, FECHA, IDJUEGO, IDUSUARIO) VALUES
('Velocidad pura',      'Controles precisos y una banda sonora que acompaña cada curva.',         9.2, 'Publicada', '2026-10-02', 1, 2),
('Un mundo enorme',     'Combates exigentes y un mundo extenso, con tramos de ritmo irregular.',  8.6, 'Publicada', '2026-10-03', 2, 3),
('Ideas originales',    'Buen diseño de niveles, aunque se vuelve repetitivo hacia el final.',    7.9, 'Publicada', '2026-10-04', 3, 4),
('Estrategia sólida',   'Mapas variados y una curva de dificultad bien ajustada.',                8.1, 'Publicada', '2026-10-05', 4, 2),
('Una aventura cálida', 'Dirección de arte cuidada y una historia que se disfruta sin prisa.',    8.8, 'Publicada', '2026-10-06', 5, 3),
('Acción sin pausa',    'Combates intensos, pero el apartado técnico aún necesita pulirse.',      7.4, 'Publicada', '2026-10-07', 6, 4),
('Buen arranque',       'Reseña en revisión: faltan probar los modos en línea.',                  6.9, 'Borrador',  '2026-10-08', 7, 2),
('Segunda opinión',     'Excelente sensación de velocidad, con pocos circuitos al inicio.',       9.0, 'Publicada', '2026-10-09', 1, 3);

-- NOTICIAS (8): TITULO, RESUMEN, CATEGORIA, FECHA, PUBLICADA, IDUSUARIO, IDJUEGO
INSERT INTO TBL_NOTICIA (TITULO, RESUMEN, CATEGORIA, FECHA, PUBLICADA, IDUSUARIO, IDJUEGO) VALUES
('Hollow Crown suma un modo cooperativo para cuatro jugadores', 'El estudio detalló las novedades de su juego de acción y rol, con progresión compartida.', 'Lanzamientos', '2026-10-08', 1, 2, 2),
('Ashen Circuit estrena nuevos niveles de puzles',              'La actualización añade retos nuevos y mejora el rendimiento en equipos modestos.',      'Indie',         '2026-10-06', 1, 3, 3),
('Cinco juegos independientes que no debes perderte',           'Una selección de títulos pequeños con ideas muy originales.',                           'Indie',         '2026-10-02', 1, 4, NULL),
('Pocket Rhythm llega con canciones nuevas cada semana',        'El juego de ritmo para iOS incorporará un calendario de estrenos musicales.',           'Movil',         '2026-10-04', 1, 2, 8),
('Nebula Drift recibe una actualización con circuitos nuevos',  'Se suman tres pistas y un modo contrarreloj para competir con amigos.',                 'Lanzamientos', '2026-10-09', 1, 3, 1),
('Los estudios apuestan por lanzamientos escalonados',          'Cada vez más equipos publican sus juegos por etapas para recibir comentarios.',         'Industria',     '2026-09-30', 1, 1, NULL),
('Lanternfall confirma su llegada a PC',                        'La aventura de exploración ya tiene versión disponible para ordenadores.',              'Lanzamientos', '2026-09-25', 1, 4, 10),
('Se prepara una jornada gamer local',                          'Nota en borrador: faltan confirmar fecha y lugar del evento.',                          'Eventos',       '2026-10-10', 0, 3, NULL);

-- ---------------------------------------------------------------------
-- PRUEBA DEL PROCEDIMIENTO
-- ---------------------------------------------------------------------
-- 1) Reseña válida: se guarda (es la reseña número 9)
CALL SP_REGISTRAR_RESENA(8, 4, 'Ritmo de bolsillo',
    'Canciones pegajosas y controles táctiles muy bien resueltos.', 8.3);

-- 2) Puntuación inválida: lanza el error 'la puntuación debe estar entre 0 y 10'.
--    Está comentada para que la importación no se detenga; quita los "--" para probarla.
-- CALL SP_REGISTRAR_RESENA(8, 3, 'Prueba', 'Esta reseña no debe guardarse.', 15);

-- ---------------------------------------------------------------------
-- COMPROBACIONES
-- ---------------------------------------------------------------------
-- Cantidad de registros (esperado: 5, 7, 6, 10, 9, 8)
SELECT 'TBL_USUARIO' AS TABLA, COUNT(*) AS REGISTROS FROM TBL_USUARIO
UNION ALL SELECT 'TBL_GENERO', COUNT(*) FROM TBL_GENERO
UNION ALL SELECT 'TBL_PLATAFORMA', COUNT(*) FROM TBL_PLATAFORMA
UNION ALL SELECT 'TBL_JUEGO', COUNT(*) FROM TBL_JUEGO
UNION ALL SELECT 'TBL_RESENA', COUNT(*) FROM TBL_RESENA
UNION ALL SELECT 'TBL_NOTICIA', COUNT(*) FROM TBL_NOTICIA;
