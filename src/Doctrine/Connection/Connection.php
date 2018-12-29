<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use Closure;
use Psr\Log\LoggerInterface;

class Connection
	extends \Doctrine\DBAL\Connection
	implements \VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\AfterCommitCallbacksConnection
{

	/** @var \Closure[] */
	private $afterCommitCallbacks = [];

	/** @var \Psr\Log\LoggerInterface */
	private $logger;

	public function setLogger(LoggerInterface $logger): void
	{
		if ($this->logger !== null) {
			throw new \VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\ConnectionLoggerAlreadyInitializedException();
		}

		$this->logger = $logger;
	}

	public function isTransactionActive(): bool
	{
		return parent::isTransactionActive();
	}

	public function commit(): void
	{
		parent::commit();
		if (!$this->isTransactionActive()) {
			$callbacks = $this->afterCommitCallbacks;
			$this->afterCommitCallbacks = [];
			foreach ($callbacks as $callback) {
				try {
					$callback();
				} catch (\Throwable $exception) {
					$message = sprintf(
						'%s: %s (uncaught exception) at %s line %s while running after commit callbacks',
						get_class($exception),
						$exception->getMessage(),
						$exception->getFile(),
						$exception->getLine()
					);

					$this->logger->error($message, [
						'exception' => $exception,
					]);
				}
			}
		}
	}

	public function rollBack(): void
	{
		parent::rollBack();
		if (!$this->isTransactionActive() || $this->isRollbackOnly()) {
			$this->afterCommitCallbacks = [];
		}
	}

	public function addAfterCommitCallback(Closure $callback): void
	{
		if ($this->logger === null) {
			throw new \VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\ConnectionRequiresLoggerException();
		}

		$this->afterCommitCallbacks[] = $callback;
	}

}
