<?php
namespace Braghim\Zf2lib\Model\Entity\DbBraghim\SchemaPublic;

use Braghim\Zf2lib\Model\AbstractEntity;

class User extends AbstractEntity
{
	/**
	 * Metadados da tabela
	 */
	public $tableName = 'user';
	public $pks = array('user_pk_user_seq' => 'pk_user');
	
	/**
	 *	Colunas da entidade
	 */
	private $pk_user;
	private $name;
	private $username;
	private $password;
	private $email;
	private $fk_role;
	private $cpf;
	private $pic;
	private $status;
	private $created;
	private $edited;

	public function setPkUser($pk_user) {
		$this->pk_user = $pk_user;
		return $this;
	}

	public function getPkUser() {
		return $this->pk_user;
	}

	public function setName($name) {
		$this->name = $name;
		return $this;
	}
	
	public function getName() {
		return $this->name;
	}

	public function setUsername($username) {
		$this->username = $username;
		return $this;
	}

	public function getUsername() {
		return $this->username;
	}

	public function setPassword($password) {
		$this->password = $password;
		return $this;
	}

	public function getPassword() {
		return $this->password;
	}

	public function setEmail($email) {
		$this->email = $email;
		return $this;
	}

	public function getEmail() {
		return $this->email;
	}

	public function setFkRole($fk_role) {
		$this->fk_role = $fk_role;
		return $this;
	}

	public function getFkRole() {
		return $this->fk_role;
	}
	
	public function getPic() {
		return $this->pic;
	}

	public function setPic($pic) {
		$this->pic = $pic;
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
	
	public function setCpf($cpf) {
		$this->cpf = $cpf;
		return $this;
	}
	
	public function getCpf() {
		return $this->cpf;
	}
}