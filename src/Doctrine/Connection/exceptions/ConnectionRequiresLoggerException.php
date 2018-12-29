<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

class ConnectionRequiresLoggerException extends \Exception
{

	public function __construct(?\Throwable $previous = null)
	{
		parent::__construct('Connection requires logger for logging callback exceptions', 0, $previous);
	}

}
