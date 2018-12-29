<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\DependencyInjection;

class DoctrineExtensionNotFoundException extends \Exception
{

	public function __construct(?\Throwable $previous = null)
	{
		parent::__construct('Could not find registered `doctrine` extension', 0, $previous);
	}

}
