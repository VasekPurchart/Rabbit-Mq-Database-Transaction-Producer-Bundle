<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use Closure;

class Connection
	extends \Doctrine\DBAL\Connection
	implements \VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\AfterCommitCallbacksConnection
{

	/** @var \Closure[] */
	private $afterCommitCallbacks = [];

	public function commit()
	{
		parent::commit();
		if (!$this->isTransactionActive()) {
			$callbacks = $this->afterCommitCallbacks;
			$this->afterCommitCallbacks = [];
			foreach ($callbacks as $callback) {
				$callback();
			}
		}
	}

	public function rollBack()
	{
		parent::rollBack();
		if (!$this->isTransactionActive() || $this->isRollbackOnly()) {
			$this->afterCommitCallbacks = [];
		}
	}

	public function addAfterCommitCallback(Closure $callback)
	{
		$this->afterCommitCallbacks[] = $callback;
	}

}
