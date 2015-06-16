<?php
namespace Braghim\Zf2lib\Model\Entity\DbBraghim\SchemaPublic;

use Braghim\Zf2lib\Model\AbstractEntity;

class Status extends AbstractEntity
{
	const ATIVO = 'ativo';
	const INATIVO = 'inativo';
	const EXCLUIDO = 'excluido';
	const BLOQUEADO = 'bloqueado';
	
	/**
	 * Metadados da tabela
	 */
	public $tableName = 'status';
	public $pks = array('pk_status');
	
	/**
	 *	Colunas da entidade
	 */
	private $pk_status;
	private $name;
	private $status;
	private $created;
	private $edited;
	
	public function setPkStatus($pk_status) {
		$this->pk_status = $pk_status;
		return $this;
	}
	
	public function getPkStatus() {
		return $this->pk_status;
	}
	
	public function setName($name) {
		$this->name = $name;
		return $this;
	}

	public function getName() {
		return $this->name;
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
