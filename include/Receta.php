<?php

namespace Clases;

use PDO;
use PDOException;

/* Clase que hereda de Conexion y realiza todas las peticiones sql relacionadas con las recetas a la  base de datos */

class Receta extends Conexion
{
    public function __construct()
    {
        parent::__construct();
    }

    public function insertReceta($titulo, $descripcion, $duracion_preparacion, $categoria, $usuario_id)
    {
        $query = "
            INSERT INTO recetas 
                (titulo, descripcion, duracion_preparacion, categoria, usuario_id)
            VALUES
                (:titulo, :descripcion, :duracion_preparacion, :categoria, :usuario_id);
        ";

        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute(array(
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'duracion_preparacion' => $duracion_preparacion,
                'categoria' => $categoria,
                'usuario_id' => $usuario_id
            ));
            return $this->conexion->lastInsertId();
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function insertIngredienteReceta($receta_id, $nombre_ingrediente, $cantidad, $medida)
    {
        // Comprobacion de la existencia del nombre de ingrediente en la base de datos de ingrediente
        $query = "SELECT id FROM ingredientes WHERE nombre = :nombre";
        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute(array('nombre' => $nombre_ingrediente));
            $ingrediente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ingrediente) {
                $ingrediente_id = $ingrediente['id'];
            } else {
                $query = "INSERT INTO ingredientes (nombre) VALUES (:nombre)";
                $stmt = $this->conexion->prepare($query);
                $stmt->execute(array('nombre' => $nombre_ingrediente));
                $ingrediente_id = $this->conexion->lastInsertId();
            }

            $query = "
            INSERT INTO receta_ingredientes 
                (receta_id, ingrediente_id, cantidad, medida)
            VALUES
                (:receta_id, :ingrediente_id, :cantidad, :medida);
        ";

            $stmt = $this->conexion->prepare($query);
            $stmt->execute(array(
                'receta_id' => $receta_id,
                'ingrediente_id' => $ingrediente_id,
                'cantidad' => $cantidad,
                'medida' => $medida
            ));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function insertPasoReceta($receta_id, $descripcion, $orden)
    {
        $query = "
            INSERT INTO pasos 
                (descripcion, orden, receta_id)
            VALUES
                (:descripcion, :orden, :receta_id);
        ";

        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute(array(
                'descripcion' => $descripcion,
                'orden' => $orden,
                'receta_id' => $receta_id
            ));
            return $stmt->rowCount();
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function getRecetasByCategoria($categoria)
    {
        $query = "
            SELECT id, titulo, descripcion, duracion_preparacion
            FROM recetas
            WHERE categoria = :categoria
            ORDER BY titulo;
        ";

        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute(array('categoria' => $categoria));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function getRecetaById($id)
    {
        $query = "SELECT * FROM recetas WHERE id = :id";
        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function getIngredientesByRecetaId($receta_id)
    {
        $query = "
            SELECT ingredientes.nombre, receta_ingredientes.cantidad, receta_ingredientes.medida
            FROM receta_ingredientes
            JOIN ingredientes ON receta_ingredientes.ingrediente_id = ingredientes.id
            WHERE receta_ingredientes.receta_id = :receta_id
        ";
        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute(['receta_id' => $receta_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function getPasosByRecetaId($receta_id)
    {
        $query = "SELECT descripcion FROM pasos WHERE receta_id = :receta_id ORDER BY orden";
        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute(['receta_id' => $receta_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function insertComentario($contenido, $usuario_id, $receta_id)
    {
        $query = "
            INSERT INTO comentarios 
                (contenido, usuario_id, receta_id)
            VALUES
                (:contenido, :usuario_id, :receta_id);
        ";

        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute(array(
                'contenido' => $contenido,
                'usuario_id' => $usuario_id,
                'receta_id' => $receta_id
            ));
            return $this->conexion->lastInsertId();
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function getComentariosByRecetaId($receta_id)
    {
        $query = "
            SELECT comentarios.contenido, comentarios.fecha, usuarios.nombre AS autor
            FROM comentarios
            JOIN usuarios ON comentarios.usuario_id = usuarios.id
            WHERE comentarios.receta_id = :receta_id
            ORDER BY comentarios.fecha DESC;
        ";

        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute(array('receta_id' => $receta_id));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }

    public function buscarRecetas($query)
    {
        // Los % alrededor del query permiten que puedan buscarse coincidencias parciales
        $query = "%$query%";

        $sql = "
            SELECT DISTINCT recetas.*
            FROM recetas
            LEFT JOIN receta_ingredientes ON recetas.id = receta_ingredientes.receta_id
            LEFT JOIN ingredientes ON receta_ingredientes.ingrediente_id = ingredientes.id
            WHERE recetas.titulo LIKE :query 
            OR recetas.descripcion LIKE :query 
            OR recetas.categoria LIKE :query 
            OR ingredientes.nombre LIKE :query
        ";

        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':query', $query, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }
}
