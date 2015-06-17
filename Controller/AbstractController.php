<?php
namespace BraghimSistemas\Zf2lib\Controller;

use BraghimSistemas\Zf2lib\Classes\Firephp;
use BraghimSistemas\Zf2lib\Enum;
use BraghimSistemas\Zf2lib\Classes\AccessControl;
use BraghimSistemas\Zf2lib\Classes\Config;
use Exception;
use Zend\Http\Headers;
use Zend\Http\Response;
use Zend\Json\Json;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\TemplatePathStack;

abstract class AbstractController extends AbstractActionController
{
	const ITEM_PER_PAGE = 20;
	const ITEM_PER_PAGE_100 = 100;
	
	/**
	 * Variaveis que serao visiveis na view
	 *
	 * @var array
	 */
	private $view = array();
	
	/**
	 * Caminho do pao para navegaçao no site.
	 * Necessario ser iniciado com NULL
	 *
	 * @var array 
	 */
	private $breadcrumbs = null;
	
	/**
	 * Possiveis tipos de mensagens do sistema
	 *
	 * @var array
	 */
	private $layoutMessages = array(
		Enum\MsgType::DANGER => array(),
		Enum\MsgType::INFO => array(),
		Enum\MsgType::SUCCESS => array(),
		Enum\MsgType::WARNING => array(),
	);
	
	/**
	 * Antes de chamar o metodo original setamos as variaveis que vao para o layout
	 * como a cor padrao para o fundo do site.
	 * 
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function dispatch(RequestInterface $request, ResponseInterface $response = null)
	{
		// Gerenciamento de permissao de acesso
		$accessControl = AccessControl::getInstance($this->getModuleName());
		$accessControl->setupPermissions(
			($this->getLogin()) ? $this->getLogin()->getFkRole() : Config::getZf2libConfig('roleDefault'),
			$this->getModuleName()
		);
		
		// Verifica se usuario tem acesso ao controlador e acao requisitado
		if (!$this->checkAccess())
		{
			if (!$this->getLogin()) {
				
				$module = $this->getModuleName() == 'application' ? '' : $this->getModuleName();
				
				return $this->redirect()->toUrl('/'.$module.'/auth/login');
			} else {
				
				// Adiciona mensagem e redireciona
				$this->addLayoutMessage("Acesso negado", Enum\MsgType::WARNING, true);
				return $this->initRedirect();
			}
		}
		
		// Se houver dados no FIREPHP para serem mostrados
		// que vieram de um redirecionamento.
		Firephp::throwRedirectLog();
		
		// Segue ritmo normal do dispatch
		return parent::dispatch($request, $response);
	}
	
	/**
	 * Redirecionamento inicial usuado quando usuario nao tem acesso ou faz login no sistema.
	 * 
	 * @return void
	 */
	public function initRedirect()
	{
		$url = "/";
		if ($this->getModuleName() == 'clientes') {
			$url = "/clientes";
		}
		
		// @TODO
//		if ($this->getLogin()) {
//			switch ($this->getLogin()->getFkRole()) {
//				case 'admin':
//
//				break;
//			}
//		}
		return $this->redirect()->toUrl($url);
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
	 * De acordo com as configuracoes no banco de dados, incluindo heranca de acesso,
	 * verificamos se o usuario tem acesso permitido a este controlador e acao.
	 * Ps.: Toda acao nao cadastrada no banco eh dada como leitura (read)
	 * 
	 * @return boolean
	 */
	private function checkAccess()
	{
		$result = false;
		
		$permission['module'] = $this->getModuleName();
		$permission['controller'] = $this->getControllerName();
		$permission['action'] = $this->getActionName();
		
		// Para garantir que nao vai chegar na acao de logar e dar redirecionamento infinito.
		// Ou que o usuario tente deslogar e nao tenha permissao
		if ($permission['controller'] == 'auth' && in_array($permission['action'], array('login', 'logout'))) {
			$result = true;

		// Todo usuario que conseguiu fazer login no admin merece acessar seu Dashboard
		} else if (
			$this->getLogin() &&
			$permission['module'] == 'clientes' &&
			$permission['controller'] == 'index' &&
			(in_array ($permission['action'], array('index', 'dashboard')))
		) {
			$result = true;
			
		} else {
			// Quando a acao nao existir na tabela action
			// assumimos que ela eh uma acao de leitura (read)
			$actionBoClass = Config::getZf2libConfig('actionBusinessClass', $this->getModuleName());
			$actionBO = new $actionBoClass();
			$action = $actionBO->get($permission['action']);
			if (!$action) {
				$permission['action'] = Config::getZf2libConfig('actionRead');
			}
			
			// Se nao tiver acesso redireciona para login
			if (AccessControl::allowed(implode('.', $permission))) {
				$result = true;
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
	 * Retorna URL da pagina anterior
	 * 
	 * @return type
	 */
	protected function getReferer() {
		return $this->getRequest()->getServer()->get('HTTP_REFERER');
	}
	
	/**
	 * Metodo para envio de email pelo sistema. As configuracoes de conexao com o servidor de email
	 * devem estar no arquivo 'config/autoload/'.$type.'.local.php'.
	 * Ps.: Este arquivo, por conter senhas, é ignorado pelo git.
	 * 
	 * @param string|array $emailTo
	 * @param string $subject
	 * @param string $htmlBody
	 * @param string $type
	 * @param string|array $replyTo
	 * @throws Exception
	 */
	protected function email($emailTo, $subject, $htmlBody, $type = 'mail', $replyTo = null)
	{
		$result = true;
		
		/**
		 * O arquivo *.local.php nao eh incluido no git, por isso as informacoes contidas
		 * nele sao seguras, diferente do arquivo global.php.
		 */
		$auth = 'config/autoload/'.$type.'.local.php';
		$authConf = file_exists($auth) ? (require $auth) : false;
		$global = require 'config/autoload/global.php';

		$options = array_merge_recursive(isset($global[$type]) ? $global[$type] : array(), $authConf);
		
		try {
			if ($options) {
				// Codifica o tipo de mensagem HTML
				$mimePart = new Mime\Part($htmlBody);
				$mimePart->type = Mime\Mime::TYPE_HTML;
				$mimeMsg = new Mime\Message();
				$mimeMsg->setParts(array($mimePart));
				
				// Cria mensagem efetivamente
				$message = new Message();
				$message->setBody($mimeMsg);
				$message->setEncoding('UTF-8');

				// Informacoes do e-mail em si
				$message->addFrom($options['connection_config']['username'], 'Braghim Sistemas');
				if ($replyTo) {
					$message->setReplyTo($replyTo);
				}
				$message->addTo($emailTo);
				$message->setSubject($subject);

				// Transportador de mensagem
				$transport = new SmtpTransport();
				$transport->setOptions(new SmtpOptions($options));
				$transport->send($message);
			} else {
				throw new \Exception("Configurações de e-mail não foram definidas ou arquivo '$type.local.php' não existe");
			}
		} catch (\Exception $e) {
			Firephp::getInstance()->err($e->__toString());
			$result = false;
		}
		return $result;
	}

	/**
	 * Adiciona uma variavel diretamente no layout do sistema.
	 * 
	 * @param string $name
	 * @param mixed $value
	 */
	protected function addLayoutVar($name, $value) {
		$this->getEvent()->getViewModel()->setVariable((string) $name, $value);
	}
	
	/**
	 * Assumimos a partir daqui que não há outro motivo para acessar
	 * o transportador de mensagens do sistema senao para enviar
	 * mensagens ao layout, mas para isso existem os metodos abaixo.
	 * Talvez futuramente haja necessidade de acessar o flashMessenger
	 * diretamente por algum motivo, mas por enquanto não.
	 * 
	 * @link addLayoutMessage() Adiciona mensagem ao layout com definicao de tipo e redirecionamento se necessario.
	 * @link getLayoutMessages() Retorna todas as mensagens ativas do sistema que foram enviadas ao layout.
	 * @link hasLayoutMessages() Retorna true caso haja mensagem de qualquer natureza ou apenas do tipo passado.
	 * 
	 * @throws Exception
	 */
	protected function flashMessenger() {
		throw new Exception("Este objeto não deve ser acessado diretamente, utilize addLayoutMessage()");
	}
	
	/**
	 * Adiciona mensagem ao layout com definicao de tipo e redirecionamento se necessario.
	 * 
	 * @param string $msg
	 * @param Enum\MsgType $type
	 * @param bool $withRedirect Caso seja para guardar a mensagem para a proxima exibicao de pagina
	 * @return AbstractController
	 * @throws Exception
	 */
	protected function addLayoutMessage($msg, $type, $withRedirect = false)
	{
		if (!array_key_exists($type, $this->layoutMessages)) {
			throw new Exception("O tipo " . $type . " não é reconhecido pelo sistema para setar mensagens.");
		}
		
		if ($withRedirect) {
			switch ($type) {
				case Enum\MsgType::DANGER:
					parent::flashMessenger()->addErrorMessage($msg);
					break;
				case Enum\MsgType::INFO:
					parent::flashMessenger()->addInfoMessage($msg);
					break;
				case Enum\MsgType::SUCCESS:
					parent::flashMessenger()->addSuccessMessage($msg);
					break;
				case Enum\MsgType::WARNING:
					parent::flashMessenger()->addWarningMessage($msg);
					break;
			}
		} else {
			$this->layoutMessages[$type][] = $msg;
		}
		return $this;
	}
	
	/**
	 * Retorna todas as mensagens ativas do sistema que foram enviadas ao layout.
	 * 
	 * @return array
	 */
	private function getLayoutMessages()
	{
		$fm = parent::flashMessenger();
		
		$fmMsgs = array();
		foreach (array_keys($this->layoutMessages) as $type)
		{
			switch ($type) {
				case Enum\MsgType::DANGER:
					$fmMsgs[Enum\MsgType::DANGER] = $fm->getErrorMessages();
					break;
				case Enum\MsgType::INFO:
					$fmMsgs[Enum\MsgType::INFO] = $fm->getInfoMessages();
					break;
				case Enum\MsgType::SUCCESS:
					$fmMsgs[Enum\MsgType::SUCCESS] = $fm->getSuccessMessages();
					break;
				case Enum\MsgType::WARNING:
					$fmMsgs[Enum\MsgType::WARNING] = $fm->getWarningMessages();
					break;
			}
		}
		return array_merge_recursive($this->layoutMessages, $fmMsgs);
	}
	
	/**
	 * Retorna true caso haja mensagem de qualquer natureza ou apenas do tipo passado.
	 * 
	 * @param Enum\MsgType $type
	 * @return boolean
	 */
	protected function hasLayoutMessages($type = null)
	{
		$result = false;
		
		$fm = parent::flashMessenger();
		
		// Mensagem normal do sistema pelo tipo
		if ($type && $this->layoutMessages[$type]) {
			$result = true;
				
		// Mensagens enviadas de outras acoes com tipo
		} else if ($type) {
			switch ($type) {
				case Enum\MsgType::DANGER:
					$result = $fm->hasErrorMessages();
				break;
				case Enum\MsgType::INFO:
					$result = $fm->hasInfoMessages();
				break;
				case Enum\MsgType::SUCCESS:
					$result = $fm->hasSuccessMessages();
				break;
				case Enum\MsgType::WARNING:
					$result = $fm->hasWarningMessages();
				break;
			}
		// Mensagens de qualquer tipo
		} else {
			// Verifica se há qualquer tipo de mensagem
			foreach (array_keys($this->layoutMessages) as $t) {
				// Mensagem normal do sistema
				if ($this->layoutMessages[$t]) {
					$result = true;
					break;
				}
			}
			
			// Verificar se o flashMessenger tem qualquer tipo de mensagem
			switch (true) {
				case $fm->hasErrorMessages():
				case $fm->hasInfoMessages():
				case $fm->hasSuccessMessages():
				case $fm->hasWarningMessages():
					$result = true;
			}
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
				$this->view[$key] = $val;
			}
		} else {
			$this->view[$name] = $value;
		}
		return $this;
	}
	
	/**
	 * Configura variavel com caminho do pao.
	 * 
	 * @param array $breadcrumbs
	 * @return AbstractController
	 */
	protected function breadcrumbs(array $breadcrumbs = array())
	{
		$module = ($this->getModuleName() == 'application') ? '' : $this->getModuleName();
		
		$this->breadcrumbs['/'.$module] = 'Início';
		$this->breadcrumbs = array_merge($this->breadcrumbs, $breadcrumbs);
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
	protected function getView($name = null, $value = null)
	{
		// Adiciona mensagens ao layout caso haja alguma
		if ($this->hasLayoutMessages()) {
			$this->addLayoutVar('layoutMessages', $this->getLayoutMessages());
		}
		
		// Adiciona ao layout informacoes de login do usuario
		$this->addLayoutVar('loginData', $this->getLogin());
		$this->addViewVar('loginData', $this->getLogin());
		
		$this->addViewVar('breadcrumbs', $this->breadcrumbs);
		
		// Se foi passada alguma variavel para view
		if ($name) {
			$this->addViewVar($name, $value);
		}
		return new ViewModel((array) $this->view);
	}
	
	/**
	 * AJAX.
	 * Retorna automaticamente as headers necessarias para json, com conteudo.
	 * SEMPRE QUE USAR UM AJAX, UTILIZE ESTE METODO PARA RETORNO.
	 * 
	 * @param mixed $result
	 * @return Response
	 */
	protected function getJsonView($result)
	{
		$headers = new Headers();
		$headers->addHeaderLine('Content-Type', 'application/json');
		
		return $this->getResponse()->setHeaders($headers)->setContent(Json::encode($result));
	}
	
	/**
	 * Retorna em string arquivo php renderizado incluindo variaveis que a ele são passadas.
	 * Ideal para enviar emails e outras coisas.
	 * 
	 * @param string $viewFile Arquivo php para renderizar, este arquivo deve ser relativo a 'module/Application/view'
	 * @param array $vars
	 * @return string
	 */
	protected function render($viewFile, array $vars = array())
	{
		$view = new ViewModel($vars);
		$view->setTemplate($viewFile);

		$resolver = new AggregateResolver();
		$resolver->attach(new TemplatePathStack(array(
			'script_paths' => array("module/". ucfirst(strtolower($this->getModuleName())) ."/view")
		)));

		$renderer = new PhpRenderer();
		$renderer->setResolver($resolver);

		return $renderer->render($view);
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
