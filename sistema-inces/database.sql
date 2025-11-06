-- Base de datos: `inces_db`
--
CREATE DATABASE IF NOT EXISTS `inces_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `inces_db`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `permisos` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre_rol`, `permisos`) VALUES
(1, 'Administrador', 'gestion_usuarios,gestion_cursos,gestion_estudiantes,gestion_docentes,auditoria,ver_reportes'),
(2, 'Coordinador', 'gestion_cursos,gestion_estudiantes,gestion_docentes,ver_reportes'),
(3, 'Instructor', 'gestion_estudiantes,ver_reportes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `rol_id` (`rol_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol_id`) VALUES
('Administrador', 'admin@inces.gob.ve', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1); -- password

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(255) NOT NULL,
  `tabla_afectada` varchar(100) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `valores_anteriores` text DEFAULT NULL,
  `valores_nuevos` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_curso` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `duracion_horas` int(11) NOT NULL,
  `estatus` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`nombre_curso`, `descripcion`, `duracion_horas`, `estatus`) VALUES
('Electricidad Básica', 'Curso introductorio a los principios de la electricidad.', 80, 'Activo'),
('Fontanería Básica', 'Aprende los fundamentos de la fontanería residencial.', 60, 'Activo'),
('Informática Básica', 'Conceptos esenciales de computación y ofimática.', 40, 'Activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `estatus` enum('Activo','Inactivo') NOT NULL DEFAULT 'Activo',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docentes`
--

CREATE TABLE `docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `especialidad` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `direccion` text DEFAULT NULL,
  `fecha_contratacion` date DEFAULT NULL,
  `estatus` enum('Activo','Inactivo','Vacaciones') NOT NULL DEFAULT 'Activo',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos_cursos`
--

CREATE TABLE `alumnos_cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idalumno` int(11) NOT NULL,
  `idcurso` int(11) NOT NULL,
  `fecha_inscripcion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estatus` enum('Inscrito','Cursando','Completado','Retirado') NOT NULL DEFAULT 'Inscrito',
  `fecha_completado` date DEFAULT NULL,
  `calificacion` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idalumno` (`idalumno`),
  KEY `idcurso` (`idcurso`),
  CONSTRAINT `alumnos_cursos_ibfk_1` FOREIGN KEY (`idalumno`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alumnos_cursos_ibfk_2` FOREIGN KEY (`idcurso`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
