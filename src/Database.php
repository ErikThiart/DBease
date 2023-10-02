<?php

namespace Core\src;
/**
 * Database Class
 *
 * This class provides a simple interface to interact with a MySQL database using PDO.
 * It offers methods for basic CRUD operations, conditional queries, and utility functions.
 *
 * Example usage:
 *
 * $db = new Database();
 * $users = $db->findAll('users', ['status' => 'active']);
 *
 */
class Database
{
    private $pdo;
    private $queryLog = [];

    /**
     * Constructor. Initializes the PDO instance.
     */
    public function __construct()
    {
        $host = '127.0.0.1';
        $db = 'my_database';
        $user = 'my_user';
        $pass = 'my_password';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Insert data into a table.
     *
     * @param string $table Table name.
     * @param array $data Associative array of column => value pairs.
     * @return bool True on success, false on failure.
     */
    public function insert($table, $data)
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $stmt = $this->pdo->prepare("INSERT INTO $table ($fields) VALUES ($placeholders)");
        foreach ($data as $key => &$value) {
            $stmt->bindParam(":$key", $value);
        }
        return $stmt->execute();
    }

    /**
     * Update records in a table based on conditions.
     *
     * @param string $table Table name.
     * @param array $data Associative array of column => value pairs to update.
     * @param array $where Associative array of conditions (column => value).
     * @return bool True on success, false on failure.
     */
    public function update($table, $data, $where)
    {
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "$key = :$key, ";
        }
        $set = rtrim($set, ', ');

        $whereClause = $this->buildWhereClause($where);

        $stmt = $this->pdo->prepare("UPDATE $table SET $set WHERE $whereClause");
        $this->bindValues($stmt, array_merge($data, $where));

        return $stmt->execute();
    }

    /**
     * Delete records from a table based on conditions.
     *
     * @param string $table Table name.
     * @param array $conditions Associative array of conditions (column => value).
     * @return bool True on success, false on failure.
     */
    public function delete($table, $conditions)
    {
        $whereClause = $this->buildWhereClause($conditions);
        $stmt = $this->pdo->prepare("DELETE FROM $table $whereClause");
        $this->bindValues($stmt, $conditions);
        return $stmt->execute();
    }

    /**
     * Select a single record from a table based on conditions.
     *
     * @param string $table Table name.
     * @param array $conditions Associative array of conditions (column => value).
     * @return array|null The fetched record or null if not found.
     */
    public function find($table, $conditions = [])
    {
        $sql = "SELECT * FROM {$table}";

        if (!empty($conditions)) {
            $columns = array_keys($conditions);
            $columnSql = implode(" AND ", array_map(fn($col) => "$col = ?", $columns));
            $sql .= " WHERE " . $columnSql;
        }

        $sql .= " LIMIT 1";  // Ensure only one record is fetched

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($conditions));

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;  // Return the result or null if not found
    }

    /**
     * Retrieve all records from a table that meet the provided conditions.
     *
     * @param string $table Table name.
     * @param array $conditions Associative array of conditions (column => value).
     * @return array Array of records.
     */
    public function findAll($table, $conditions = [])
    {
        $whereClause = $this->buildWhereClause($conditions);
        $stmt = $this->pdo->prepare("SELECT * FROM $table $whereClause");
        $this->bindValues($stmt, $conditions);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count records in a table based on conditions.
     *
     * @param string $table Table name.
     * @param array $conditions Associative array of conditions (column => value).
     * @return int The count of matching records.
     */
    public function count($table, $conditions = [])
    {
        $whereClause = $this->buildWhereClause($conditions);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM $table $whereClause");
        $this->bindValues($stmt, $conditions);
        $stmt->execute();
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Get the last inserted row's ID.
     *
     * @return int The last inserted ID.
     */
    public function lastInsertId()
    {
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Check if a column exists in a table.
     *
     * @param string $table Table name.
     * @param string $column Column name.
     * @return bool True if column exists, false otherwise.
     */
    public function columnExists($table, $column)
    {
        try {
            $result = $this->pdo->query("SELECT $column FROM $table LIMIT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Check if a table exists in the database.
     *
     * @param string $table Table name.
     * @return bool True if table exists, false otherwise.
     */
    public function tableExists($table)
    {
        try {
            $result = $this->pdo->query("SELECT 1 FROM $table LIMIT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Insert multiple rows into a table.
     *
     * @param string $table Table name.
     * @param array $data Array of associative arrays for data to insert.
     * @return bool True on success, false on failure.
     */
    public function insertMultiple($table, $data)
    {
        $fields = implode(', ', array_keys($data[0]));
        $placeholders = rtrim(str_repeat('(' . substr(str_repeat('?,', count($data[0])), 0, -1) . '),', count($data)), ',');
        $values = array_merge(...array_map('array_values', $data));

        $stmt = $this->pdo->prepare("INSERT INTO $table ($fields) VALUES $placeholders");
        return $stmt->execute($values);
    }

    /**
     * Execute a raw SQL query.
     *
     * @param string $sql SQL query string.
     * @param array $params Optional array of parameters to bind to the query.
     * @return array Array of records for SELECT queries, or true/false for other queries.
     */
    public function raw($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        // For SELECT queries, return the result set
        if (stripos($sql, 'SELECT') === 0) {
            return $stmt->fetchAll();
        }

        // For other queries (INSERT, UPDATE, DELETE), return true on success, false on failure
        return $stmt->rowCount() > 0;
    }

    /**
     * Log a query and its parameters.
     *
     * @param string $query SQL query.
     * @param array $params Query parameters.
     */
    private function logQuery($query, $params = [])
    {
        $this->queryLog[] = ['query' => $query, 'params' => $params];
    }

    /**
     * Get the query log.
     *
     * @return array Array of logged queries and parameters.
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    private $selectFields = '*';
    private $limitValue;
    private $offsetValue;

    /**
     * Specify columns to select in a query.
     *
     * @param string $fields Comma-separated list of column names.
     * @return $this Database instance for method chaining.
     */
    public function select($fields)
    {
        $this->selectFields = $fields;
        return $this;
    }

    /**
     * Limit the number of rows returned in a query.
     *
     * @param int $limit Maximum number of rows to return.
     * @return $this Database instance for method chaining.
     */
    public function limit($limit)
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Offset the rows returned in a query.
     *
     * @param int $offset Number of rows to skip.
     * @return $this Database instance for method chaining.
     */
    public function offset($offset)
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Retrieve records from a table based on conditions with optional select, limit, and offset.
     * Can also execute custom SQL queries.
     *
     * @param string|array $tableOrSql Table name, custom SQL query, or array with SQL and params.
     * @param array $conditions Associative array of conditions (column => value).
     * @return array Array of records.
     */
    public function fetchWithOffset($tableOrSql, $conditions = [])
    {
        $limitClause = $this->limitValue ? "LIMIT $this->limitValue" : '';
        $offsetClause = $this->offsetValue ? "OFFSET $this->offsetValue" : '';

        if (is_string($tableOrSql)) {
            // Handle standard SELECT query
            $whereClause = $this->buildWhereClause($conditions);
            $stmt = $this->pdo->prepare("SELECT $this->selectFields FROM $tableOrSql $whereClause $limitClause $offsetClause");
            $this->bindValues($stmt, $conditions);
        } elseif (is_array($tableOrSql) && isset($tableOrSql['sql'])) {
            // Handle custom SQL query
            $stmt = $this->pdo->prepare($tableOrSql['sql']);
            $this->bindValues($stmt, $tableOrSql['params']);
        } else {
            throw new InvalidArgumentException("Invalid argument type for fetchWithOffset method.");
        }

        $stmt->execute();
        return $stmt->fetchAll();

        $this->selectFields = '*';
        $this->limitValue = null;
        $this->offsetValue = null;
    }
}
