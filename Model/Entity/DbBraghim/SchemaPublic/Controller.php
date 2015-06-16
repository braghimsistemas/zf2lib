<?php
namespace Braghim\Zf2lib\Model\Entity\DbBraghim\SchemaPublic;

use Braghim\Zf2lib\Model\AbstractEntity;

class Controller extends AbstractEntity
{
	/**
	 * Metadados da tabela
	 */
	public $tableName = 'controller';
	public $pks = array('pk_controller');
	
	/**
	 *	Colunas da entidade
	 */
	private $pk_controller;
	private $name;
	private $fk_module;
	private $status;
	private $created;
	private $edited;
	
	public function getPkController() {
		return $this->pk_controller;
	}
	
	public function getName() {
		return $this->name;
	}

	public function getFkModule() {
		return $this->fk_module;
	}

	public function setPkController($pk_controller) {
		$this->pk_controller = $pk_controller;
		return $this;
	}
	
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	public function setFkModule($fk_module) {
		$this->fk_module = $fk_module;
		return $this;
	}
		
	public function setStatus($status) {
		$this->status = $status;
		return $this;
	}

	public function getStatus() {
		return $this->status;
	}
	
	public function setCreated($created) {
		$this->created = $created;
		return $this;
	}

	public function getCreated() {
		return $this->created;
	}
	
	public function setEdited($edited) {
		$this->edited = $edited;
		return $this;
	}
	
	public function getEdited() {
		return $this->edited;
	}
}
