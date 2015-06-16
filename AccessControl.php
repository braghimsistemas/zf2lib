<?php
namespace Braghim\Zf2lib;

use Braghim\Zf2lib\Model\Entity\DbBraghim\SchemaPublic\Role as EntityRole;
use Application\Model\SchemaPublic\PermissionBusiness;
use Zend\Permissions\Rbac\Rbac;
use Zend\Permissions\Rbac\Role;
use Zend\Authentication\Storage\Session as AuthStorage;

class AccessControl
{
	private static $instance;
	
	
	/**
	 * @var \Zend\Permissions\Rbac\Rbac 
	 */
	private $control;
	
	/**
	 * @var AuthStorage
	 */
	private $authStorage;
	
	private function __construct() { }
	private function __clone() { }
	
	/**
	 * Singleton
	 * 
	 * @return \Braghim\Zf2lib\AccessControl\AccessControl
	 */
	public static function getInstance($authNamespace = null)
	{
		if (self::$instance == null) {
			if (!$authNamespace) {
				throw new Exception("Não há namespace para login");
			}
			
			// Cria instancia desta classe
			self::$instance = new self();
			
			// Recupera sessao de login para este modulo
			self::$instance->authStorage = new AuthStorage("Auth".$authNamespace);
		}
		return self::$instance;
	}
	
	/**
	 * Recupera as permissoes do banco de dados e as distribui no objeto \Zend\Permissions\Rbac\Rbac.
	 * 
	 * Role Based Access Controll
	 * Metodo de controle de acesso que permite heranca de permissoes.
	 * 
	 * @param type $userRole
	 */
	public function setupPermissions($userRole = EntityRole::VISITANTE)
	{
		// Primeiro role, referente diretamente ao cargo do usuario.
		$mainRole = new Role($userRole);
		
		// Permissoes para o cargo principal
		$permissionBO = new PermissionBusiness();
		$permissions = $permissionBO->getListByRole($userRole);
		
		foreach ($permissions as $allow) {
			$mainRole->addPermission(
				$allow->module->getPkModule() . '.' .
				$allow->controller->getName() . '.' .
				$allow->permission->getFkAction()
			);
		}
		
		$this->control = new Rbac();
		$this->control->addRole($mainRole);
	}
	
	/**
	 * Retorna true caso o usuario tenha a dada permissao.
	 * 
	 * @param string $permission
	 * @return boolean
	 */
	public static function allowed($permission)
	{
		if (!self::$instance->control) {
			return false;
		}
		
		// Login do usuario para pegar role.
		$role = self::$instance->getLogin() ? self::$instance->getLogin()->getFkRole() : EntityRole::VISITANTE;
		
		// DEV pode TUDO! =D
		if ($role == EntityRole::DEV) {
			return true;
		}
		
		return self::$instance->control->isGranted($role, $permission);
	}
	
	/** AUTH **/
	
	/**
	 * @return AuthStorage
	 */
	public function getAuthStorage() {
		return $this->authStorage;
	}
	
	/**
	 * Retorna dados de login do usuario.
	 * 
	 * @return \Braghim\Zf2lib\Model\Entity\DbBraghim\SchemaPublic\User
	 */
	public function getLogin() {
		return $this->getAuthStorage()->read();
	}
	
	/**
	 * Verifica se o cargo do usuário logado é SUPERIOR ou igual ao cargo requerido (parametro).
	 * 
	 * @param type $roleId
	 * @return boolean
	 * @throws \Exception
	 */
	public static function upperHierarchy($roleId)
	{
		// Cargo usado para testar
		$roleBO = new \Clientes\Model\SchemaPublic\RoleBusiness();
		$role = $roleBO->get($roleId);
		if (!$role) {
			throw new \Exception("O cargo '$roleId' não foi definido no sistema");
		}
		
		// Cargo do usuario logado.
		$loggedRole = $roleBO->get(self::$instance->getLogin()->getFkRole());
		
		// Resultado true caso o cargo do usuario logado seja igual ou superior
		// daquele passado para teste.
		$result = false;
		if ($loggedRole->getOrder() <= $role->getOrder()) {
			$result = true;
		}
		return $result;
	}
	
	/**
	 * Verifica se o cargo do usuário logado é INFERIOR ou igual ao cargo requerido (parametro).
	 * 
	 * @param type $roleId
	 * @return boolean
	 * @throws \Exception
	 */
	public static function lowerHierarchy($roleId)
	{
		// Cargo usado para testar
		$roleBO = new \Clientes\Model\SchemaPublic\RoleBusiness();
		$role = $roleBO->get($roleId);
		if (!$role) {
			throw new \Exception("O cargo '$roleId' não foi definido no sistema");
		}
		
		// Cargo do usuario logado.
		$loggedRole = $roleBO->get(self::$instance->getLogin()->getFkRole());
		
		// Resultado true caso o cargo do usuario logado seja igual ou superior
		// daquele passado para teste.
		$result = false;
		if ($loggedRole->getOrder() >= $role->getOrder()) {
			$result = true;
		}
		return $result;
	}
}
