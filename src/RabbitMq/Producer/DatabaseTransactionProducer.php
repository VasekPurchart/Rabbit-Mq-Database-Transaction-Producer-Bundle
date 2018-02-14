<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\RabbitMq\Producer;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\AfterCommitCallbacksConnection;

class DatabaseTransactionProducer implements \OldSound\RabbitMqBundle\RabbitMq\ProducerInterface
{

	/** @var \OldSound\RabbitMqBundle\RabbitMq\ProducerInterface */
	private $wrappedProducer;

	/** @var \VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\AfterCommitCallbacksConnection */
	private $databaseConnection;

	public function __construct(
		ProducerInterface $wrappedProducer,
		AfterCommitCallbacksConnection $databaseConnection
	)
	{
		$this->wrappedProducer = $wrappedProducer;
		$this->databaseConnection = $databaseConnection;
	}

	/**
	 * @param string $messageBody
	 * @param string $routingKey
	 * @param mixed[] $additionalProperties
	 */
	public function publish($messageBody, $routingKey = '', $additionalProperties = [])
	{
		if (!$this->databaseConnection->isTransactionActive()) {
			$this->wrappedProducer->publish($messageBody, $routingKey, $additionalProperties);
		} else {

			$this->databaseConnection->addAfterCommitCallback(function () use ($messageBody, $routingKey, $additionalProperties) {
				$this->wrappedProducer->publish($messageBody, $routingKey, $additionalProperties);
			});
		}
	}

}
