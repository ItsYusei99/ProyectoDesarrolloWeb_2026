-- Blog simple: modelo ER en 3FN (MySQL 8)
-- ROL, USUARIO, CATEGORIA, POST, POST_CATEGORIA (N:M), VOTO (like/dislike, anónimo permitido)
CREATE DATABASE IF NOT EXISTS blog_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blog_system;

CREATE TABLE rol (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(30) NOT NULL UNIQUE
    CHECK (nombre IN ('administrador','editor','escritor'))
);

CREATE TABLE usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  rol_id INT NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id) REFERENCES rol(id),
  INDEX idx_usuario_nombre (nombre)
);

CREATE TABLE categoria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(80) NOT NULL UNIQUE,
  descripcion VARCHAR(255) NULL
);

CREATE TABLE post (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(200) NOT NULL,
  contenido TEXT NOT NULL,
  fecha_publicacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  portada_url VARCHAR(255) NULL,
  autor_id INT NOT NULL,
  CONSTRAINT fk_post_autor FOREIGN KEY (autor_id) REFERENCES usuario(id),
  INDEX idx_post_autor (autor_id),
  INDEX idx_post_fecha (fecha_publicacion)
);

-- Un post puede tener varias categorías y una categoría varios posts (N:M).
CREATE TABLE post_categoria (
  post_id INT NOT NULL,
  categoria_id INT NOT NULL,
  PRIMARY KEY (post_id, categoria_id),
  CONSTRAINT fk_pc_post FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE,
  CONSTRAINT fk_pc_cat FOREIGN KEY (categoria_id) REFERENCES categoria(id) ON DELETE CASCADE
);

-- Sin comentarios; like/dislike de registrados o anónimos.
-- Anónimo: usuario_id NULL + ip obligatoria. Registrado: usuario_id + ip NULL.
CREATE TABLE voto (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  usuario_id INT NULL,
  tipo ENUM('like','dislike') NOT NULL,
  ip VARCHAR(45) NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_voto_post FOREIGN KEY (post_id) REFERENCES post(id) ON DELETE CASCADE,
  CONSTRAINT fk_voto_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE,
  UNIQUE KEY uq_voto_usuario (post_id, usuario_id),
  CONSTRAINT chk_voto_origen CHECK (
    (usuario_id IS NOT NULL AND ip IS NULL) OR
    (usuario_id IS NULL AND ip IS NOT NULL)
  ),
  INDEX idx_voto_post (post_id)
);

-- Roles base
INSERT INTO rol (nombre) VALUES ('administrador'), ('editor'), ('escritor');
