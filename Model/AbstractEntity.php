<?php
namespace Braghim\Zf2lib\Model;

abstract class AbstractEntity
{
	/**
	 * Metadados padrao da tabela da tabela
	 */
	public $schema;
	public $tableName;
	public $pks = array();
	
	public function __construct(array $data = array()) {
		$this->exchangeArray($data);
	}
	/**
	 * Metodo chamado pelo proprio Zend para popular a Entidade
	 * que estende a esta.
	 * 
	 * @param array $data
	 */
	public function exchangeArray(array $data = array())
	{
		foreach ($data as $key => $value) {
			$setter = $this->setterName($key);
			if (method_exists($this, $setter)) {
				$this->$setter($value);
			}
		}
		return $this;
	}
	
	/**
	 * Retorna array com atributos e seus respectivos valores, mas
	 * separados por pks e fields
	 * 
	 * @return array
	 */
	public function toArray()
	{
		// default
		$result = array("pks" => null, "fields" => null);
		
		// Reflete a classe para pegar seus atributos privados
		$reflect = new \ReflectionClass($this);
		$privateAttrs = $reflect->getProperties(\ReflectionProperty::IS_PRIVATE);
		
		// Para cada atributo pegamos seu valor e definimos o retorno
		foreach ($privateAttrs as $attr) {
			$getter = $this->getterName($attr->name);
			
			if (in_array($attr->name, $this->pks)) {
				$result['pks'][$attr->name] = $this->$getter();
			} else {
				$result['fields'][$attr->name] = $this->$getter();
			}
		}
		return $result;
	}
	
	public function getterName($attrName)
	{
		$result = 'get';
		foreach (explode("_", $attrName) as $part) {
			$result .= ucfirst(strtolower($part));
		}
		return $result;
	}
	
	public function setterName($attrName)
	{
		$result = 'set';
		foreach (explode("_", $attrName) as $part) {
			$result .= ucfirst(strtolower($part));
		}
		return $result;
	}
	
}