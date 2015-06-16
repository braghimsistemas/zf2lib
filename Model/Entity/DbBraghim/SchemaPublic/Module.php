<?php
namespace Braghim\Zf2lib\Model\Entity\DbBraghim\SchemaPublic;

use Braghim\Zf2lib\Model\AbstractEntity;

class Module extends AbstractEntity
{
	const ADMIN = 'admin';
	const AJAX = 'ajax';
	const APPLICATION = 'application';
	const DNEIMPORT = 'dneimport';
	const WEBSERVICE = 'web-service';
	
	/**
	 * Metadados da tabela
	 */
	public $tableName = 'module';
	public $pks = array('pk_module');
	
	/**
	 *	Colunas da entidade
	 */
	private $pk_module;
	private $status;
	private $created;
	private $edited;
	
	public function getPkModule() {
		return $this->pk_module;
	}

	public function setPkModule($pk_module) {
		$this->pk_module = $pk_module;
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
