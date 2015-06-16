<?php
namespace BraghimSistemas\Zf2lib\Model;

use BraghimSistemas\Zf2lib\Classes\Config;
use BraghimSistemas\Zf2lib\Model\AbstractEntity;
use BraghimSistemas\Zf2lib\Model\SqlFileHandle;
use BraghimSistemas\Zf2lib\Zendfix\SequenceFeatureFix;
use Exception;
use ReflectionClass;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\TableIdentifier;
use Zend\Db\TableGateway\TableGateway;

abstract class AbstractGateway
{
	const CREATE = 'create';
	const UPDATE = 'update';
	
	private static $adapter;
	private static $database;
	private static $schema;
	
	private $entity;
	
	public function __construct()
	{
		if (!self::$adapter) {
			throw new Exception("Não foi configurado nenhum Adapter padrão de Banco de Dados");
		}
		
		// Do nome da classe (ex. Application\Model\SchemaPublic\Gateway\PermissionGateway)
		// retira o nome do schema a que este pertence
		$matches = null;
		$match = preg_match("/Schema\w*/", get_class($this), $matches);
		if ($match !== 1) {
			throw new Exception("O namespace de '".get_class($this)."' não contém o nome do schema a que pertence. Algo parecido com 'SchemaPublic'");
		}
		
		// Namespace identificado da entidade
		$entityNamespace = Config::getZf2libConfig('entityNamespace') . '\Db'.ucfirst(strtolower(self::$database)).'\\'.current($matches);
		
		// Vamos encontrar agora o nome da classe da entidade
		$nameParts = explode("\\", get_class($this));
		$className = preg_replace("/Gateway$/", "", array_pop($nameParts));
		
		$entity = $entityNamespace.'\\'.$className;
		
		// Instancia e Inicia o adaptador
		$this->entity = new $entity();
	}
	
	public static function setDefaultAdapter(Adapter $adapter, $database, $schema = null) {
		self::$adapter = $adapter;
		self::$database = $database;
		
		// Schema
		if ($schema) {
			self::$schema = $schema;
		}
	}
	
	public static function getDefaultAdapter() {
		return self::$adapter;
	}
	
	public static function getDefaultDatabase() {
		return self::$database;
	}
	
	public static function getDefaultSchema() {
		return self::$schema;
	}
	
	public function beginTransaction() {
		return self::$adapter->getDriver()->getConnection()->beginTransaction();
	}
	
	public function commit() {
		return self::$adapter->getDriver()->getConnection()->commit();
	}
	
	public function rollback() {
		return self::$adapter->getDriver()->getConnection()->rollback();
	}
	
	public function getDb() {
		
		// Se tabela fica em um schema proprio ou usa o schema padrao do banco
		$tableIdentifier = new TableIdentifier(
			$this->entity->tableName,
			($this->entity->schema) ? $this->entity->schema : self::$schema
		);
		
		// Caso no array de pk da tabela tenha uma sequence (serial)
		// isso eh usado para retornar o ultimo pk inserido na tabela
		$features = null;
		foreach ($this->entity->pks as $seqName => $col) {
			if ($seqName && is_string($seqName)) {
				$features[] = new SequenceFeatureFix($col, $seqName);
			}
		}
		
		return new TableGateway($tableIdentifier, self::$adapter, $features);
	}
	
	/**
	 * Metodos para serem usados pela classe que implementa a esta
	 */
	
	/**
	 * Prepara uma query a partir de um arquivo .sql que deve ficar na pasta
	 * Model/SqlResources/ que é relativa ao caminho do gateway.
	 * 
	 * @param string $filename
	 * @param array $params
	 * @return Handle
	 */
	public function fileQuery($filename, array $params = array())
	{
		// Primeiro descobrir onde estao os arquivos SQL
		
		// Reflexiona a entidade para pegar os arquivos SQL relativos a este diretorio
		$ref = new ReflectionClass(get_class($this));
		$entityDirname = dirname($ref->getFileName());
		
		// Prefixo do arquivo sql
		$parts = explode("\\", get_class($this->entity));
		$prefixFile = strtolower(array_pop($parts));
		
		// Caminho para os arquivos Sql
		$filepath = preg_replace("/\/Gateway/", "", $entityDirname) . "/SqlResources/";
		
		// Define e valida arquivo
		$file = $filepath . $prefixFile . DIRECTORY_SEPARATOR . $filename . '.sql';
		if (!file_exists($file)) {
			throw new \Exception("Arquivo (".$file.") com a query não existe");
		}
		
		/**
		 * O arquivo vai ser procurado sempre em "Model/SqlResources/{nome minusculo da entidade}/$filename"
		 */
		$file = file_get_contents($file);
		
		$handle = new SqlFileHandle($file);
		$handle->setParams($params);
		$handle->setArrayObjectPrototype($this->entity);
		
		// Retorna instancia de manipulador de query
		return $handle;
	}
	
	/**
	 * @params mixed O metodo recebe parametros diversos e ilimitados.
	 * Cada um desses parametros eh tratado como um id de primary key.
	 * 
	 * @return mixed | Fabrica\Lib\Model\AbstractEntity
	 */
	public function get()
	{
		// Lista de ids do metodo
		$ids = func_get_args();
		
		// Recupera o select e adiciona clausula onde somente
		// os registros diferentes de 'excluido' devem ser retornados
		$select = $this->getDb()->getSql()->select();
		$select->where(array('status <> ?' => Config::getZf2libConfig('statusExcluido')));
		
		// Efetua consulta setando os pks (uma tabela pode ter dois pks)
		// Por ondem de chegada, ou seja, o metodo deve receber os ids
		// na mesma ordem que foram adicionados na entidade
		foreach (array_values($this->entity->pks) as $pos => $pkName) {
			$select->where(array($pkName => $ids[$pos]));
		}
		
		// Efetua a consulta
		$rowset = $this->getDb()
			->selectWith($select)
			->setArrayObjectPrototype($this->entity);
		
		return $rowset->current();
	}
	
	/**
	 * Cria ou atualiza um registro no banco de dados
	 * 
	 * @param string $type self::CREATE | self::UPDATE
	 * @param AbstractEntity $data
	 * @return bool
	 * @throws Exception
	 */
	public function save($type, $data)
	{
		if (is_array($data)) {
			
			// Popula entidade com dados vindo do array
			$this->entity->exchangeArray($data);
			
		} else if ($data instanceof AbstractEntity) {
			
			// Simplesmente substitui entidade.
			$this->entity = $data;
		}
		
		$result = false;
		
		// CREATE ou UPDATE?
		switch ($type) {
			case self::CREATE:
				
				// Recupera os valores dos atributos em formato de array
				$attrs = $this->entity->toArray();
				
				// Define campos que serao inseridos no banco
				// Colunas com valor null serao removidos do insert
				$fields = array();
				foreach ($attrs["fields"] as $colName => $colValue) {
					if (!is_null($colValue)) {
						$fields[$colName] = $colValue;
					}
				}
				
				// Se algum PK tiver valor definido (nao eh auto increment)
				// adicionamos ao array de insercao
				foreach ($attrs['pks'] as $pkName => $pkValue) {
					if (!is_null($pkValue)) {
						$fields[$pkName] = $pkValue;
					}
				}
				
				$db = $this->getDb();
				
				// Executa insert, isso retorna a quantidade de linhas afetadas.
				// (Como sera que isso se comporta no caso de chave composta?)
				$result = $db->insert($fields);
				
				if ($db->lastInsertValue) {
					$result = $db->lastInsertValue;
				}
				
			break;
			case self::UPDATE:
				
				// Data de edicao
				$this->entity->setEdited(date('Y-m-d H:i:s'));
				
				// Recupera os valores dos atributos em formato de array
				$attrs = $this->entity->toArray();
				
				// Valida se o valor das PKs foram preenchidos
				foreach ($attrs["pks"] as $pk) {
					if (is_null($pk)) {
						throw new Exception(
							get_class($this->entity) . ": Contém chaves primárias sem valor para update."
						);
					}
				}
				// Efetua update
				$result = $this->getDb()->update($attrs["fields"], $attrs["pks"]);
			break;
			default:
				throw new Exception(
					"Não entendemos o que quer salvar, " . __CLASS__ . "::UPDATE ou " . __CLASS__ . "::CREATE ?"
				);
		}
		return $result;
	}
	
	/**
	 * Faz um delete logico, ou seja, seta o status do registro como excluido
	 * 
	 * @param mixed $id Recebe um numero indeterminado de parametros e cada parametro
	 * eh tratado como uma primary key
	 * @return boolean
	 */
	public function delete()
	{
		$row = call_user_func_array(array($this, 'get'), func_get_args());
		if ($row) {
			$row->setStatus(Config::getZf2libConfig('statusExcluido'));
			return $this->save(self::UPDATE, $row);
		}
		return false;
	}
	
	/**
	 * Delete FISICO.
	 * 
	 * @param mixed $id Recebe um numero indeterminado de parametros e cada parametro
	 * eh tratado como uma primary key
	 * @return boolean
	 */
	public function remove()
	{
		$result = false;
		
		$row = call_user_func_array(array($this, 'get'), func_get_args());
		if ($row) {
			$entityArgs = $row->toArray();
			$result = (bool) $this->getDb()->delete($entityArgs['pks']);
		}
		return $result;
	}
}