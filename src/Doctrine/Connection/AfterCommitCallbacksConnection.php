<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use Closure;

interface AfterCommitCallbacksConnection
{

	public function addAfterCommitCallback(Closure $callback): void;

	public function isTransactionActive(): bool;

}
