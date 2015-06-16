<?php
namespace BraghimSistemas\Zf2lib\Model\Entity\DbBraghimSistemas\SchemaPublic;

use BraghimSistemas\Zf2lib\Model\AbstractEntity;

class Role extends AbstractEntity
{
	const ADMIN = 'admin';
	const CLIENTE = 'cliente';
	const DEV = 'dev';
	const VISITANTE = 'visitante';
	
	/**
	 * Metadados da tabela
	 */
	public $tableName = 'role';
	public $pks = array('pk_role');
	
	/**
	 *	Colunas da entidade
	 */
	private $pk_role;
	private $name;
	private $admin;
	private $order;
	private $status;
	private $created;
	private $edited;

	
	public function getPkRole() {
		return $this->pk_role;
	}

	public function setPkRole($pk_role) {
		$this->pk_role = $pk_role;
		return $this;
	}
	
	public function setName($name) {
		$this->name = $name;
		return $this;
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setAdmin($admin) {
		$this->admin = $admin;
		return $this;
	}
	
	public function getAdmin() {
		return $this->admin;
	}
	
	public function setOrder($order) {
		$this->order = $order;
		return $this;
	}
	
	public function getOrder() {
		return $this->order;
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
