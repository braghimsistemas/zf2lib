<?php
namespace BraghimSistemas\Zf2lib\Model\Entity\DbBraghimSistemas\SchemaPublic;

use BraghimSistemas\Zf2lib\Model\AbstractEntity;

class Action extends AbstractEntity
{
	const ACTION_NEW = 'new';
	const ACTION_DELETE = 'delete';
	const ACTION_READ = 'read';
	const ACTION_EDIT = 'edit';
	
	/**
	 * Metadados da tabela
	 */
	public $tableName = 'action';
	public $pks = array('pk_action');
	
	/**
	 *	Colunas da entidade
	 */
	private $pk_action;
	private $status;
	private $created;
	private $edited;
	
	public function getPkAction() {
		return $this->pk_action;
	}

	public function getStatus() {
		return $this->status;
	}

	public function getCreated() {
		return $this->created;
	}

	public function getEdited() {
		return $this->edited;
	}

	public function setPkAction($pk_action) {
		$this->pk_action = $pk_action;
		return $this;
	}

	public function setStatus($status) {
		$this->status = $status;
		return $this;
	}

	public function setCreated($created) {
		$this->created = $created;
		return $this;
	}

	public function setEdited($edited) {
		$this->edited = $edited;
		return $this;
	}
}
