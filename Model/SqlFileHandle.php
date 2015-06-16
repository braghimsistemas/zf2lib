<?php
namespace BraghimSistemas\Zf2lib\Model;

use Zend\Paginator\Paginator;

class SqlFileHandle
{
	private $query;
	
	private $params = array();
	
	private $arrayPrototype;
	
	/**
	 * Construtor. Transforma a string de query comum em objeto.
	 * 
	 * @param string $query
	 */
	public function __construct($query) {
		$this->query = $query;
	}
	
	/**
	 * Adiciona varios parametros de consulta.
	 * 
	 * @param array $params
	 * @return \BraghimSistemas\Zf2lib\Model\SqlFileHandle
	 */
	public function setParams(array $params) {
		foreach ($params as $name => $value) {
			$this->addParam($name, $value);
		}
		return $this;
	}
	
	/**
	 * Retorna lista de parametros.
	 * 
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}
	
	/**
	 * Adiciona apenas um parametro a consulta.
	 * 
	 * @param type $name
	 * @param type $value
	 * @param type $default
	 * @param type $treat
	 * @return \BraghimSistemas\Zf2lib\Model\SqlFileHandle
	 */
	public function addParam($name, $value = null, $default = null, $treat = null)
	{
		// Tratamento. Ex:. % ficando de 'valor' para '%valor%'
		if ($value && $treat) {
			$value = $treat.$value.$treat;
		}
		
		// Tem valor padrao e $value esta vazio
		if ($default && !$value) {
			$value = $default;
		}
		
		$this->params[':'.$name] = $value;
		return $this;
	}
	
	/**
	 * Substitui diretamente na query o parametro para NULL.
	 * 
	 * @param string $name
	 * @return \BraghimSistemas\Zf2lib\Model\SqlFileHandle
	 */
	public function addParamNull($name)
	{
		$this->query = preg_replace("/(:$name)/i", "NULL", $this->query);
		return $this;
	}
	
	/**
	 * Seta prototipo de retorno para query.
	 * 
	 * @param AbstractEntity|EntityListPrototype $arrayPrototype
	 * @return \DneImport\Model\SqlResources\Handle
	 * @throws \Exception
	 */
	public function setArrayObjectPrototype($arrayPrototype)
	{
		// Validacao do prototipo
		if (!$arrayPrototype instanceof AbstractEntity && !$arrayPrototype instanceof EntityListPrototype) {
			throw new \Exception("A entidade deve ser instancia de AbstractEntity ou de EntityListPrototype");
		}
		
		$this->arrayPrototype = $arrayPrototype;
		return $this;
	}
	
	/**
	 * Adiciona parametro de OFFSET na query.
	 * 
	 * @param int $offset
	 * @return \BraghimSistemas\Zf2lib\Model\SqlFileHandle
	 */
	public function offset($offset) {
		if (!preg_match("/(OFFSET)(\s+)?\d+/i", $this->query)) {
			$this->query .= " OFFSET ".$offset;
		}
		return $this;
	}
	
	/**
	 * Adiciona parametro de LIMIT na query.
	 * 
	 * @param type $limit
	 * @return \BraghimSistemas\Zf2lib\Model\SqlFileHandle
	 */
	public function limit($limit) {
		if (!preg_match("/(LIMIT)(\s+)?\d+/i", $this->query)) {
			$this->query .= " LIMIT ".$limit;
		}
		return $this;
	}
	
	/**
	 * Conta quantidade total de dados que serao retornados pela dada query.
	 * (Paginator)
	 * 
	 * @return int
	 */
	public function countTotal()
	{
		// Executa query
		$adapter = AbstractGateway::getDefaultAdapter();
		$resultSet = $adapter->query("SELECT COUNT(1) FROM (".$this->query.") as sql", (array) $this->params);
		
		return $resultSet->current()->count;
	}
	
	/**
	 * Executa query e retorna resultado com paginação.
	 * 
	 * @param AbstractEntity|EntityListPrototype $arrayPrototype
	 * @return \Zend\Paginator\Paginator
	 */
	public function paginator($arrayPrototype = null)
	{
		// Se preciso troca prototipo de retorno
		if ($arrayPrototype) {
			$this->setArrayObjectPrototype($arrayPrototype);
		}
		
		// PS.: Dentro do paginator será chamado metodo execute aqui desta classe.
		return new Paginator(new HandlePaginator($this));
	}
	
	/**
	 * Executa a query, seta prototipo de retorno.
	 * 
	 * @param AbstractEntity|EntityListPrototype $arrayPrototype
	 * @return type
	 */
	public function execute($arrayPrototype = null)
	{
		// Executa query
		$adapter = AbstractGateway::getDefaultAdapter();
		$resultSet = $adapter->query($this->query, (array) $this->params);
		
		// Se preciso troca prototipo de retorno
		if ($arrayPrototype) {
			$this->setArrayObjectPrototype($arrayPrototype);
		}
		
		// Seta prototipo de retorno
		$resultSet->setArrayObjectPrototype($this->arrayPrototype);
		return $resultSet;
	}
}