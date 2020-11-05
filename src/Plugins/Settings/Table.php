<?php
namespace WordpressLib\Plugins\Settings;

class Table {

	protected $name;
	protected $schema;
	protected $db;

	public function __construct(string $name, string $schema, Database $db) {
		$this->name = $name;
		$this->schema = $schema;
		$this->db = $db;
	}

	public function createIfMissing() {
		if (!$this->existsInDb()) {
			$this->create();
		}
	}

	public function getTableName() {
		return $this->name;
	}

	public function getTableSchema() {
		return $this->schema;
	}

	public function existsInDb() {
		if ($result = $this->db->getHandle()->query(sprintf("SELECT name FROM sqlite_master WHERE type='table' and name='%s'", $this->getTableName()))) {
			return (Boolean)($result->fetchArray());
		}
		return FALSE;
	}

	protected function create() {
		return $this->db->getHandle()->exec(sprintf("CREATE TABLE %s %s", $this->getTableName(), $this->getTableSchema()));
	}

}

