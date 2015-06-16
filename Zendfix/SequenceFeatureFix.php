<?php
namespace Braghim\Zf2lib\Zendfix;

use Zend\Db\TableGateway\Feature\SequenceFeature;

/**
 *	O Zend tem um problema nesta classe na hora de chamar a sequencia. Talvez um bug.
 *	Vamos sobreescrever o metodo para arrumar isso.
 * 
 *	@author Marco A. Braghim <marco.a.braghim@gmail.com>
 */
class SequenceFeatureFix extends SequenceFeature
{
	public function nextSequenceId()
	{
		$platform = $this->tableGateway->adapter->getPlatform();
		$platformName = $platform->getName();

		switch ($platformName) {
			case 'Oracle':
				$sql = 'SELECT ' . $platform->quoteIdentifier($this->sequenceName) . '.NEXTVAL as "nextval" FROM dual';
				break;
			case 'PostgreSQL':
				/**
				 * Este é o lugar onde tivemos de fazer alteração
				 */
				$sql = "SELECT NEXTVAL('$this->sequenceName')";
				break;
			default :
				return null;
		}

		$statement = $this->tableGateway->adapter->createStatement();
		$statement->prepare($sql);
		$result = $statement->execute();
		$sequence = $result->current();
		unset($statement, $result);
		return $sequence['nextval'];
	}
}
