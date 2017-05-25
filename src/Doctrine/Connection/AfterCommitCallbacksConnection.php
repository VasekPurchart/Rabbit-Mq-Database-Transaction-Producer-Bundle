<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use Closure;

interface AfterCommitCallbacksConnection
{

	public function addAfterCommitCallback(Closure $callback);

	/**
	 * @return bool
	 */
	public function isTransactionActive();

}
