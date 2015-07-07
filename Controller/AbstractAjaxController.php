<?php

namespace Braghim\Zf2lib\Controller;

use Ajax\Model\SchemaPublic\ActionBusiness;
use Braghim\Zf2lib\AccessControl;
use Braghim\Zf2lib\Enum\MsgType;
use Braghim\Zf2lib\Model\Entity\Db041print\SchemaPublic\Action;
use Braghim\Zf2lib\Model\Entity\Db041print\SchemaPublic\Role;
use Braghim\Zf2lib\Model\Entity\Db041print\SchemaPublic\User;
use Zend\Http\PhpEnvironment\Response as EnvResponse;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container as Session;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ResponseInterface;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\Validator\InArray;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

abstract class AbstractAjaxController extends AbstractActionController
{
	const GET = HttpRequest::METHOD_GET;
	const POST = HttpRequest::METHOD_POST;
	const PUT = HttpRequest::METHOD_PUT;
	const DELETE = HttpRequest::METHOD_DELETE;
	
	const ACCESS_CONTROL_NAMESPACE = 'AccessControlNamespace';

	private $view = array(
		'url' => null,
		'method' => null,
		'params' => array(),
		'data' => array(),
		'errors' => array(),
		'validation' => array(),
	);

	/**
	 * Antes de chamar o metodo original setamos as variaveis que vao para o layout
	 * como a cor padrao para o fundo do site.
	 * 
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function dispatch(Request $request, Response $response = null) {
		
		// Verifica permissao de acesso.
		if (!$this->checkAccess($request)) {
			return $this->redirect()->toRoute("ajax/access-denied");
		}
		
		// Segue ritmo normal do dispatch
		return parent::dispatch($request, $response);
	}
	
	/**
	 *  Este metodo garante que um usuario não conseguira executar ação nenhuma quando
	 * nao tem acesso para tal.
	 */
	public function accessDeniedAction()
	{
		// Chegando aqui provavelmente terá algum erro na sessao para ser mostrado.
		$session = new Session(self::ACCESS_CONTROL_NAMESPACE);
		
		if (isset($session->view['errors']) && $session->view['errors']) {
			$this->view['errors'] = $session->view['errors'];
			$this->view['validation'] = $session->view['validation'];
		}
		$session->getManager()->getStorage()->clear(self::ACCESS_CONTROL_NAMESPACE);
		
		return $this->getView(false);
	}
	
	/**
	 * Verifica permissao de acesso.
	 * 
	 * @param RequestInterface $request
	 * @return type
	 */
	public function checkAccess(Request $request)
	{
		// True porque por padrao eh checado o acesso de accessDeniedAction (nao tem acesso)
		$result = true;
		
		// Validação do parametro MODULO
		$module = $request->getQuery('module', 'application');
		
		$this->validate('module', $module, array('NotEmpty', 'InArray'), array(
			'InArray' => array(
				'haystack' => array('clientes', 'application'),
				'messages' => array(
					InArray::NOT_IN_ARRAY => "Verifique o parâmetro 'module'"
				)
			)
		));
		
		$session = new Session(self::ACCESS_CONTROL_NAMESPACE);

		// Parametro Module não encontrado, assim não sabemos
		// que tipo de login ele tem.
		if (!$this->isAllValid()) {
			
			$this->setError(MsgType::DANGER, "Acesso Negado");
			$session->view = $this->view;
			$result = false;
			
		// Agora verificamos se o usuario tem permissao de acesso
		// configurado pelo banco de dados
		} else if ($this->getActionName() != 'access-denied') {
			
			// Puxa configuracoes de acessos do banco de dados
			$accessControl = AccessControl::getInstance($module);
			$accessControl->setupPermissions(($this->getLogin()) ? $this->getLogin()->getFkRole() : Role::VISITANTE);

			// Setor do sistema
			$permission = array(
				'module' => $this->getModuleName(),
				'controller' => $this->getControllerName(),
				'action' => $this->getActionName()
			);
			
			// Quando a acao nao existir na tabela action
			// assumimos que ela eh uma acao de leitura (read)
			$actionBO = new ActionBusiness();
			$action = $actionBO->get($permission['action']);
			if (!$action) {
				$permission['action'] = Action::ACTION_READ;
			}
			
			// Verifica se usuario tem acesso
			$allowed = AccessControl::allowed(implode('.', $permission));
			
			if (!$allowed) {
				$this->setError(MsgType::DANGER, "Acesso Negado");
				$session->view = $this->view;
				$result = false;
			}
		}
		return $result;
	}
	
	/**
	 * Retorna informacoes do usuario logado
	 * 
	 * @return User
	 */
	protected function getLogin() {
		return AccessControl::getInstance()->getLogin();
	}
	
	/**
	 * Retona o nome do modulo atual
	 * 
	 * @return string
	 */
	public function getModuleName() {
		$controllerParts = (array) explode('\\', strtolower($this->params('controller')));
		return current($controllerParts);
	}
	
	/**
	 * Retona o nome do controlador atual
	 * 
	 * @return string
	 */
	public function getControllerName() {
		$controllerParts = (array) explode('\\', strtolower($this->params('controller')));
		return array_pop($controllerParts);
	}
	
	/**
	 * Retorna o nome da acao atual
	 * 
	 * @return string
	 */
	public function getActionName() {
		return $this->params('action');
	}

	/**
	 * Verifica se o method de requisicao eh valido.
	 * 
	 * @param type $allowed
	 * @return boolean
	 */
	public function checkMethod($allowed = HttpRequest::METHOD_GET) {
		$result = true;
		if ($this->getRequest()->getServer()->get('REQUEST_METHOD') != $allowed) {

			$this->response->setStatusCode(EnvResponse::STATUS_CODE_405);
			$this->setError(MsgType::WARNING, "Method not allowed");
			$result = false;
		}
		return $result;
	}

	/**
	 * Adiciona uma variavel qualquer ao conjunto de variaveis que
	 * aparecerao na view.
	 * 
	 * @param string|array $name
	 * @param mixed $value
	 */
	protected function addViewVar($name, $value = null) {
		if (is_array($name)) {
			foreach ($name as $key => $val) {
				$this->view['data'][$key] = $val;
			}
		} else if ($value) {
			$this->view['data'][$name] = $value;
		} else {
			$this->view['data'] = $name;
		}
		return $this;
	}

	/**
	 * Adiciona parametro que nao passou na validacao.
	 * 
	 * @param string $name
	 * @param string $msg
	 * @param string $type
	 * @return AbstractAjaxController
	 */
	protected function addValidation($name, $msg) {
		$this->view['validation'][$name] = $msg;
		return $this;
	}

	/**
	 * Efetua validacao automatica.
	 * 
	 * @param string $name Nome que o parametro recebe para saber de que campo esta tratando.
	 * @param mixed $value Valor do parametro que esta sendo validado.
	 * @param array $types Metodo validador do Zend.
	 * @param array $options @see \Zend\Validator\AbstractValidator
	 * @return void
	 */
	protected function validate($name, $value, array $types, array $options = null) {
		foreach ($types as $type) {

			$className = "\Zend\Validator\\$type";
			$validator = new $className();

			// Caso tenha sido passado algum parametro de opcoes.
			if (isset($options[$type])) {
				$validator->setOptions($options[$type]);
			}

			if (!$validator->isValid($value)) {
				$this->addValidation($name, $validator->getMessages());
			}
		}
	}

	/**
	 * Retorna true caso nao tenha nehhuma validacao sem sucesso.
	 * 
	 * @return type
	 */
	protected function isAllValid() {
		return (bool) !$this->view['validation'];
	}

	/**
	 * Seta erro do sistema.
	 * 
	 * @param type $type
	 * @param type $msg
	 * @return AbstractAjaxController
	 */
	protected function setError($type, $msg) {
		$this->view['errors'][$type][] = $msg;
		return $this;
	}

	/**
	 * Adiciona efetivamente aquelas variaveis que foram adicionadas
	 * na view.
	 * 
	 * @param type $name
	 * @param type $value
	 * @return ViewModel
	 */
	protected function getView($name = null, $value = null) {
		if ($name) {
			$this->addViewVar($name, $value);
		}

		// Se houve alguma validacao que nao deu sucesso.
		if (!$this->isAllValid()) {
			$this->setError(MsgType::WARNING, "Verifique os erros de validação dos parâmetros.");
		}

		/**
		 * Setting up return values
		 */
		$this->view['url'] = $this->getRequest()->getUriString();
		$this->view['method'] = $this->getRequest()->getMethod();

		// Adiciona parametros
		$params = array();
		$params += $this->params()->fromPost();
		$params += $this->params()->fromQuery();
		$this->view['params'] = $params;
		
		return new JsonModel((array) $this->view);
	}

	/**
	 * Garantir que os controladores terao implementado os metodos abaixo, para evitar que, por exemplo,
	 * um use o nome createAction e outro use newAction para o mesmo tipo de acao do sistema.
	 * 
	 * O sistema de controle de acesso usa esses nomes para este tipo de acao e qualquer acao diferente
	 * desta e dada como leitura (read) entao, estas acoes tem permissoes especiais.
	 * 
	 * @see AccessControl
	 */
	abstract public function deleteAction();
	abstract public function editAction();
	abstract public function newAction();
}
