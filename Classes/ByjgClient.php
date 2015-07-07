<?php
namespace BraghimSistemas\Zf2lib\Classes;

use Exception;
use Zend\Http\Client;
use Zend\Http\Request;

class ByjgClient
{
	const HOST = "http://www.byjg.com.br/site/webservice.php/ws/cep";
	const CONFIG_FILE = 'config/autoload/byjgclient.local.php';
	
	/**
	 * Efetua consulta de cep.
	 * 
	 * @param type $cep
	 * @return type
	 * @throws Exception
	 */
	public function addressFromCep($cep)
	{
		if (!file_exists(self::CONFIG_FILE)) {
			throw new Exception("Arquivo de configurações do cliente BYJG não existe");
		}
		$config = include self::CONFIG_FILE;
		
		$result = array('found' => false);
		try {
			// Requisicao ao BYJG que prove base de dados gratuita para CEP
			$byJg = new Client(self::HOST);
			$byJg->setMethod(Request::METHOD_POST);
			$byJg->setParameterPost(array(
				'httpmethod' => 'obterlogradouroauth',
				'cep' => $cep,
				'usuario' => $config['username'],
				'senha' => $config['password']
			));
			$response = $byJg->send();

			if ($response->isOk()) {

				// captura resultado e organiza dados
				$body = preg_replace("/^OK\|/", "", $response->getBody());

				// Separa as partes do CEP
				$parts = explode(", ", $body);
				if (count($parts) <= 1) {
					throw new Exception("Resposta ByJG não pode suprir CEP como esperado");
				}
				
				$result['found'] = true;
				list($result['logradouro'], $result['bairro'], $result['cidade'], $result['estado'], $result['codIbge']) = $parts;
			}
		} catch (Exception $e) {
			Firephp::getInstance()->err($e->__toString());
		}
		return $result;
	}
}
