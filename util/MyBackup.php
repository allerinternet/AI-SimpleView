<?php

class MyBackup {

	private $db_host;
	private $db_user;
	private $db_pass;
	private $db_name;
	private $db_tables = NULL;
	private $db;

	public function __construct($host = NULL, $user = NULL, $pass = NULL, $name = NULL)
	{
		if (!function_exists('mysql_connect')) {
			exit("PHP does not support MySQL. Please re-compile.\n");
		}
		$this->db_host = $host;
		$this->db_user = $user;
		$this->db_pass = $pass;
		$this->db_name = $name;
	}

	public function database_connect() {
		$this->db = mysql_connect($this->db_host, $this->db_user, $this->db_pass);
		if ($this->db) {
			mysql_select_db($this->db_name, $this->db);
		} else {
			exit("Could not connect to database.\n");
		}
	}

	/**
	*	Load the tables, using array or commaseperated string as input
	* if no tables are sent we default to all tables in database
	*/
	public function tables_prepare($tables = NULL) {
		// get all tables in database
		if ($tables == NULL) {
			$this->db_tables = array();
			$result = mysql_query('SHOW TABLES', $this->db);
			while ($row = mysql_fetch_row($result)) {
				$this->db_tables[] = $row[0];
			}
		} else {
			// we already have tables in object, use them
			$this->db_tables = is_array($tables) ? $tables : explode(',', $tables);
		}
	}

	public function tables_print() {
		print_r($this->db_tables);
	}

	/**
	* Dump the database, if filename is specified we save the dump
	* to file, else send it to standard output
	*/
	public function database_backup($filename = NULL) {
		$fh = NULL;
		if ($filename != NULL) {
			if (!$fh = @fopen($filename, 'w+')) {
				printf("Could not open %s for writing.\n", $filename);
				exit();
			}
		}
		$data = NULL;
		foreach ($this->db_tables as $table) {
			// Get the database schema for table
			$data .= "DROP TABLE IF EXISTS `$table`;".PHP_EOL.PHP_EOL;
			$result = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table, $this->db));
			$data .= $result[1].";".PHP_EOL.PHP_EOL;
			$result = mysql_query('SELECT * FROM '.$table, $this->db);
			$num_fields = mysql_num_fields($result);

			$data .= "LOCK TABLES `$table` WRITE;".PHP_EOL;
			while ($row = mysql_fetch_row($result)) {
				$data .= 'INSERT INTO '.$table.' VALUES(';
				for ($i=0; $i < $num_fields; $i++) {
					$row[$i] = addslashes($row[$i]);
					$row[$i] = str_replace(array("\r\n", "\r", "\n"), '\\r\\n', $row[$i]);
					if (isset($row[$i])) {
						$data .= '"'.$row[$i].'"';
					} else {
						$data .= '""';
					}
					if ($i < ($num_fields-1)) {
						$data .= ',';
					}
				}
				$data .= ');'.PHP_EOL;
			}
			$data .= "UNLOCK TABLES;".PHP_EOL;
		}
		if($fh) {
			fwrite($fh, $data);
			fclose($fh);
			return "Wrote dump to file: $filename\n";
		} else {
			return $data;
		}
	}
	



}

