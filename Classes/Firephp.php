<?php
namespace BraghimSistemas\Zf2lib;

use Zend\Log\Logger;
use Zend\Log\Writer\FirePhp as WriterFirePhp;
use Zend\Session\Container;

class Firephp
{
	const FIREBUG_NAMESPACE = 'FIREBUG';
	
	private static $instance;
	
	private static $sessionData = array();
	
	private function __construct() { }
	
	private function __clone() { }
	
	/**
	 * Inicia o FirePHP para log da aplicacao no console do navegador.
	 */
	public static function getInstance()
	{
		if (self::$instance == null) {
			
			// Inicia FirePHP, mas desabilitado
			$writer = new WriterFirePhp();
			$writer->getFirePhp()->getFirePhp()->setEnabled(false);
			self::$instance = new Logger();
			self::$instance->addWriter($writer);
			
			// Se foi setada variavel no apache ou .htaccess
			// ex.: .htaccess
			// 
			// SetEnv FIREBUG true
			if (getenv(self::FIREBUG_NAMESPACE) == "true" || getenv(self::FIREBUG_NAMESPACE) == "") {
				$writer->getFirePhp()->getFirePhp()->setEnabled(true);
				self::$instance->warn("FirePHP iniciado");
			}
		}
		return self::$instance;
	}
	
	/**
	 * Adiciona mensagem que sera lancada ao FirePHP somente apos redirecionar a pagina.
	 * 
	 * @param mixed $data
	 */
	public static function addRedirectLog($data, $type = Logger::INFO)
	{
		// Adiciona ao storage interno o conteudo de acordo com o tipo
		self::$sessionData[$type][] = $data;
		
		// Substitui na sessao
		$session = new Container(__CLASS__);
		$session->data = self::$sessionData;
	}
	
	/**
	 * Joga dados do firephp salvo na sessao por redirecionamento.
	 * 
	 * @return mixed
	 */
	public static function throwRedirectLog()
	{
		$session = new Container(__CLASS__);
		// Recupera dados
		$data = (array) $session->data;
		
		// Nomes possiveis dos tipos de mensagem
		// que o Zend usa como metodo.
		// 
		// Para entender melhor faça.:
		// \Zend\Debug\Debug::dump(get_class_methods(self::getInstance()));
		// exit;
		$methodTypeName = array(
			Logger::ALERT => 'alert',
			Logger::CRIT => 'crit',
			Logger::DEBUG => 'debug',
			Logger::EMERG => 'emerg',
			Logger::ERR => 'err',
			Logger::INFO => 'info',
			Logger::NOTICE => 'notice',
			Logger::WARN => 'warn',
		);
		
		// Joga dados no firephp
		foreach ($data as $type => $contents) {
			$method = $methodTypeName[$type];
			foreach ($contents as $value) {
				self::getInstance()->$method($value);
			}
		}
		// Apaga sessao
		$session->getManager()->getStorage()->clear(__CLASS__);
		return $data;
	}
}