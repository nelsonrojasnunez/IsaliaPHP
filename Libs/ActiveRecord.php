<?php

/**
 * Clase base para la implementación de ActiveRecord
 */
class ActiveRecord
{
    /**
     * Conexión a la base de datos
     * @var PDO
     */
    protected $connection;

    /**
     * Nombre de la tabla
     * @var string
     */
    protected $table;

    /**
     * Parámetros de la clase
     * @var array
     */
    protected $params = [];

    /**
     * Conecta a la base de datos
     * @return bool
     */
    private function connect()
    {

        if ($this->connection) {
            return TRUE;
        }

        try {
            $this->connection = new PDO(
                Config::CONNECTION_STRING,
                Config::USER,
                Config::PASSWORD,
                Config::PARAMETERS
            );
            return TRUE;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        $this->connect();
    }

    /**
     * Asigna un valor a un parámetro
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Obtiene el valor de un parámetro
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->params[$name] ?? null;
    }

    /**
     * Busca un elemento en la tabla seleccionada basada en el identidicador
     * @param int $id
     * @return array
     */
    public function find($id)
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(':id', $id);
        $statement->execute();
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca todos los elementos en la tabla seleccionada basada en una condición
     * @param string $where
     * @param array $params
     * @return array
     */
    public function findAll($where, $params)
    {
        $query = "SELECT * FROM {$this->table} WHERE {$where}";
        $statement = $this->connection->prepare($query);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Busca todos los elementos en la tabla seleccionada basada en una consulta SQL
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function findBySql($sql, $params)
    {
        $statement = $this->connection->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda un nuevo elemento en la tabla seleccionada
     * @param array $data
     * @return bool
     */
    public function save($data = null)
    {
        if ($data == null && count($this->params) > 0) {
            $data = $this->params;
        }
        $data['id'] = $this->generateId();

        $columns = implode(', ', array_keys($data));
        $values = ':' . implode(', :', array_keys($data));
        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";
        $statement = $this->connection->prepare($query);
        foreach ($data as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }

        
        try {   
            $result = $statement->execute();
            return $this->id;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un elemento desde la tabla seleccionada basada en el identidicador
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "{$key} = :{$key}, ";
        }
        $set = rtrim($set, ', ');
        $query = "UPDATE {$this->table} SET {$set} WHERE id = :id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(':id', $id);
        foreach ($data as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        return $statement->execute();
    }

    /** 
     * Elimina un elemento desde la tabla seleccionada basada en el identidicador
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(':id', $id);
        return $statement->execute();
    }

    /**
     * Genera un ID aleatorio
     * @return string
     */
    public function generateId()
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3) . time();
    }

    /**
     * Inicia una transacción en caso de requerirla
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }


    /**
     * Acepta los cambios realizados dentro de la transacción
     */
    public function commit()
    {
        $this->connection->commit();
    }


    /**
     * Rechaza los cambios realizados dentro de la transacción
     */
    public function rollback()
    {
        $this->connection->rollback();
    }

    /**
     * Cierra la conexión una vez que el objeto sale de memoria
     */
    public function __destruct()
    {
        $this->connection = null;
    }
}

// Example usage
// class User extends ActiveRecord
// {
//     protected $table = 'users';
// }

// $user = new User();
// $userData = [
//     'name' => 'John Doe',
//     'email' => 'john@example.com',
//     'password' => 'secret'
// ];
// $user->save($userData);

// $userId = 1;
// $userData = [
//     'name' => 'Jane Doe',
//     'email' => 'jane@example.com',
//     'password' => 'newsecret'
// ];
// $user->update($userId, $userData);

// $user->delete($userId);

// $userData = $user->find($userId);
// print_r($userData);