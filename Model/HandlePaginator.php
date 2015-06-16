<?php

namespace Braghim\Zf2lib\Model;

use Zend\Paginator\Adapter\AdapterInterface;

/**
 * Esta classe foi criada porque o Zend não teria como suportar nossa classe SqlFileHandle.
 * Baseado na Classe do Zend \Zend\Paginator\Adapter\DbSelect.
 * 
 * @author Marco A. Braghim <marco.a.braghim@gmail.com>
 */
class HandlePaginator implements AdapterInterface
{
	/**
	 * @var SqlFileHandle
	 */
	protected $handle;
	
	/**
	 * @var int
	 */
	protected $totalCount;

	/**
	 * Construct.
	 * 
	 * @param SqlFileHandle $handle
	 */
	public function __construct(SqlFileHandle $handle) {
		$this->handle = $handle;
	}

	/**
	 * Returns an array of items for a page.
	 *
	 * @param  int $offset           Page offset
	 * @param  int $itemCountPerPage Number of items per page
	 * @return array
	 */
	public function getItems($offset, $itemCountPerPage)
	{
		// Seta LIMIT e OFFSET na query original antes de executa-la no banco.
		$this->handle->offset($offset);
		$this->handle->limit($itemCountPerPage);

		return $this->handle->execute();
	}

	/**
	 * Returns the total number of rows in the result set.
	 *
	 * @return int
	 */
	public function count()
	{
		if (!$this->totalCount) {
			$this->totalCount = $this->handle->countTotal();
		}
		return $this->totalCount;
	}
}
