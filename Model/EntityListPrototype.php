<?php
namespace Braghim\Zf2lib\Model;

class EntityListPrototype
{
	public function __construct(array $entities)
	{
		// Entidades implicitas
		foreach ($entities as $entity) {
			
			if ($entity instanceof AbstractEntity) {
				$className = $this->getSimpleClassName(get_class($entity));
				$this->{$className} = $entity;
			}
		}
	}
	
	/**
	 * Retorna somente o nome da classe sem o namespace
	 * 
	 * @param type $fullClassName
	 * @return type
	 */
	private function getSimpleClassName($fullClassName)
	{
		$parts = explode('\\', strtolower($fullClassName));
		return array_pop($parts);
	}
	
	/**
	 * Popula classe com entidades passadas como parametro
	 * 
	 * @param array $data
	 */
	public function exchangeArray(array $data = array())
	{
		// Objetos adicionadas a esta classe
		foreach ($this as $entity) {
			if ($entity instanceof AbstractEntity)
			{
				// Lista de resultados
				foreach ($data as $key => $value) {
				
					// Nome do setter correspondente a coluna
					$setter = $entity->setterName($key);
					// Nome do setter em caso de coluna tratada
					$entityClassName = $this->getSimpleClassName(get_class($entity));
					$treatAttrName = preg_replace("/^".$entityClassName."_/", "", $key);
					$newSetter = $entity->setterName($treatAttrName);
					// Se for um campo tratado nometabela_coluna ex.: department_status
					// PS.: Este nome eh baseado no nome da classe da entidade, ou seja, 
					// para nomes compostos (ex.: department_discharge) deve ser usado o
					// nome da classe da entidade que ele pertence (departmentdischarge)
					if ($key !== $treatAttrName && method_exists($entity, $newSetter)) {
						$entity->$newSetter($value);
						
						unset($data[$key]);
						
					// Se for um campo comum da entidade.
					} else if (method_exists($entity, $setter)) {
						$entity->$setter($value);
						
						unset($data[$key]);
					}
				}
			}
		}
		
		// Chegando aqui nao deve mais haver nenhum item em
		// $data, mas os que houverem guardamos em $this->notmatch
		foreach($data as $key => $value) {
			if (!isset($this->notmatch)) {
				$this->notmatch = new \stdClass();
			}
			$this->notmatch->{$key} = $value;
		}
	}
	
	/**
	 * Pegadinha do malandro!
	 * Este metodo na verdade retorna um stdClass com os
	 * itens desta classe.
	 * 
	 * @return \stdClass
	 */
	public function toArray()
	{
		$result = new \stdClass();
		
		// Objetos adicionadas a esta classe
		foreach ($this as $key => $entity) {
			$result->$key = clone $entity;
		}
		return $result;
	}
}