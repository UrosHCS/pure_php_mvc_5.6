<?php

namespace vendor\base;

use PDO;

/**
* Connection class that uses PDO to connect to the database.
* Every query is executed by prepare an execute methods to prevent SQL injection.
*/
class DBConnection {

	/**
	* Instance of the PDO class
	*/
	private $conn;

	public function __construct() {
		$this->conn = $this->getConnection();
	}

	/**
	 * @return PDO instance
	 */
	protected function getConnection() {
		$config = require_once __DIR__ . '/../../app/config.php';
		$dbConfig = $config['db'];
		
		$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
		return new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password'], $pdo_options);
	}

	/**
	 * Every method in this class ends up calling this method.
	 * Only this method executes queries in the database.
	 * @param string $preparedQuery a prepared sql query with placeholders for values
	 * @param string $preparedValues values to insert into the prepared query
	 * @param bool $insertingValues whether or not this is an INSERT query
	 * @return PDOStatement instance on which usualy fetchAll() method is called
	 */
	public function query($preparedQuery, $preparedValues, $insertingValues = false) {
		$statement = $this->conn->prepare($preparedQuery);
		$success = $statement->execute($preparedValues);
		
		if ($insertingValues) {
			return $success;
		}
		return $statement;
	}

	/**
	 * Preform a SELECT query
	 * @param string $tableName name of the table from where to SELECT
	 * @param array $columns columns to select
	 * @param string $preparedCondition a prepared sql condition with placeholders for values
	 * @param string $preparedValues values to insert into the prepared condition
	 * @return PDOStatement instance on which usualy fetchAll() method is called
	 */
	public function select($tableName, $columns, $preparedCondition = '', $preparedValues = []) {

		$columns = implode(', ', $columns);

		if ($preparedCondition !== '') {
			$preparedCondition = ' WHERE ' . $preparedCondition;
		}
		$preparedQuery = "SELECT {$columns} FROM {$tableName}{$preparedCondition};";

		return $this->query($preparedQuery, $preparedValues);
	}

	/**
	 * Preform a SELECT * FROM ... query
	 * @param string $tableName name of the table from where to SELECT
	 * @param string $preparedCondition a prepared sql condition with placeholders for values
	 * @param string $preparedValues values to insert into the prepared condition
	 * @return PDOStatement instance on which usualy fetchAll() method is called
	 */
	public function selectAll($tableName, $preparedCondition = '', $preparedValues = []) {
		return $this->select($tableName, ['*'], $preparedCondition, $preparedValues);
	}

	/**
	 * Preform a SELECT * FROM $tableName WHERE id = $id query
	 * @param string $tableName name of the table from where to SELECT
	 * @param int $id the id to find
	 * @return PDOStatement instance on which usualy fetchAll() method is called
	 */
	public function selectById($tableName, $id) {
		$preparedCondition = 'id = :id';
		$preparedValues = [':id' => $id];
		return $this->select($tableName, ['*'], $preparedCondition, $preparedValues);
	}

	/**
	 * Base method for executing INSERT queries. Only a single row can be inserted
	 * @param string $tableName name of the table from where to SELECT
	 * @param array $data the data to insert in the form of $columnName => $columnValue pairs
	 * @return PDOStatement instance on which usualy fetchAll() method is called
	 */
	public function insertInto($tableName, $data) {
		$columns = implode(', ', array_keys($data));

		$preparedData = $this->prepareData(':', $data);
		$preparedColumns = implode(', ', array_keys($preparedData));

		$preparedQuery = "INSERT INTO {$tableName} ({$columns}) VALUES ({$preparedColumns});";

		$insertingValues = true;
		return $this->query($preparedQuery, $preparedData, $insertingValues);
	}

	/** 
	 * Add a prefix before every key.
	 * @param string $prefix
	 * @param array $data
	 * @return array
	 */
	private function prepareData($prefix, $data) {
		$preparedData = [];
		foreach ($data as $key => $value) {
		    $preparedData[$prefix . $key] = $value;
		}
		return $preparedData;
	}

	/**
	 * This method exists so that the Model class
	 * doesn't need to acces PDO class directly.
	 */
	public function fetchClass() {
		return PDO::FETCH_CLASS;
	}	

	/**
	 * This method exists so that the Auth class
	 * doesn't need to acces PDO class directly.
	 */
	public function fetchColumn() {
		return PDO::FETCH_COLUMN;
	}
}