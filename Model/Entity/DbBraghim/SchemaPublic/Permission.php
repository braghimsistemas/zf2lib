<?php
namespace BraghimSistemas\Zf2lib\Model\Entity\DbBraghimSistemas\SchemaPublic;

use BraghimSistemas\Zf2lib\Model\AbstractEntity;

class Permission extends AbstractEntity
{
	/**
	 * Metadados da tabela
	 */
	public $tableName = 'permission';
	public $pks = array('fk_action', 'fk_controller', 'fk_role'); // Chave composta ;)
	
	/**
	 *	Colunas da entidade
	 */
	private $fk_action;
	private $fk_controller;
	private $fk_role;
	private $status;
	private $created;
	private $edited;
	
	public function getFkAction() {
		return $this->fk_action;
	}

	public function getFkController() {
		return $this->fk_controller;
	}

	public function getFkRole() {
		return $this->fk_role;
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

	public function setFkAction($fk_action) {
		$this->fk_action = $fk_action;
		return $this;
	}

	public function setFkController($fk_controller) {
		$this->fk_controller = $fk_controller;
		return $this;
	}

	public function setFkRole($fk_role) {
		$this->fk_role = $fk_role;
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
