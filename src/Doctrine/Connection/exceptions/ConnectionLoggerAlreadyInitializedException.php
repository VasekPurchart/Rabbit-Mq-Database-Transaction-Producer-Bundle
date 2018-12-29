<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

class ConnectionLoggerAlreadyInitializedException extends \Exception
{

	public function __construct(?\Throwable $previous = null)
	{
		parent::__construct('Logger has been already initialized', 0, $previous);
	}

}
