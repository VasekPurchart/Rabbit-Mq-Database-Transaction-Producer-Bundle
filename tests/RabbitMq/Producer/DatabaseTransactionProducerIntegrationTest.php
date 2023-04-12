<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\RabbitMq\Producer;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PHPUnit\Framework\Assert;
use Psr\Log\LoggerInterface;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\Connection;

class DatabaseTransactionProducerIntegrationTest extends \PHPUnit\Framework\TestCase
{

	public function testWithoutTransactionSendMessageImmediately(): void
	{
		$connection = $this->getConnection();

		$message = 'message';
		$wasAlreadyPublished = false;

		$originalProducer = $this
			->getMockBuilder(Producer::class)
			->disableOriginalConstructor()
			->getMock();
		$originalProducer
			->expects(self::once())
			->method('publish')
			->with(Assert::callback(function ($receivedMessage) use ($message, &$wasAlreadyPublished) {
				$wasAlreadyPublished = true;

				return $receivedMessage === $message;
			}));

		$databaseTransactionProducer = new DatabaseTransactionProducer($originalProducer, $connection);

		$connection->query('SELECT 1');
		Assert::assertFalse($wasAlreadyPublished);
		$databaseTransactionProducer->publish($message);
		Assert::assertTrue($wasAlreadyPublished);
	}

	public function testMessageIsSentAfterTransaction(): void
	{
		$connection = $this->getConnection();

		$message = 'message';
		$wasAlreadyPublished = false;

		$originalProducer = $this
			->getMockBuilder(Producer::class)
			->disableOriginalConstructor()
			->getMock();
		$originalProducer
			->expects(self::once())
			->method('publish')
			->with(Assert::callback(function ($receivedMessage) use ($message, &$wasAlreadyPublished) {
				$wasAlreadyPublished = true;

				return $receivedMessage === $message;
			}));

		$databaseTransactionProducer = new DatabaseTransactionProducer($originalProducer, $connection);

		$connection->transactional(function () use (
			$connection,
			$originalProducer,
			$databaseTransactionProducer,
			$message,
			&$wasAlreadyPublished
		): void {
			$connection->query('SELECT 1');
			Assert::assertFalse($wasAlreadyPublished);
			$databaseTransactionProducer->publish($message);
			Assert::assertFalse($wasAlreadyPublished);
		});
		Assert::assertTrue($wasAlreadyPublished);
	}

	public function testMessageIsNeverSentIfTransactionIsNotCompleted(): void
	{
		$connection = $this->getConnection();

		$message = 'message';

		$originalProducer = $this
			->getMockBuilder(Producer::class)
			->disableOriginalConstructor()
			->getMock();
		$originalProducer
			->expects(self::never())
			->method('publish');

		$databaseTransactionProducer = new DatabaseTransactionProducer($originalProducer, $connection);

		$connection->beginTransaction();
		$connection->query('SELECT 1');
		$databaseTransactionProducer->publish($message);
	}

	public function testMessageIsThrownAwayIfTransactionWasRolledBack(): void
	{
		$connection = $this->getConnection();

		$message = 'message';
		$wasAlreadyPublished = false;

		$originalProducer = $this
			->getMockBuilder(Producer::class)
			->disableOriginalConstructor()
			->getMock();
		$originalProducer
			->expects(self::once())
			->method('publish')
			->with(Assert::callback(function ($receivedMessage) use ($message, &$wasAlreadyPublished) {
				$wasAlreadyPublished = true;

				return $receivedMessage === $message;
			}));

		$databaseTransactionProducer = new DatabaseTransactionProducer($originalProducer, $connection);

		$connection->beginTransaction();
		$connection->query('SELECT 1');
		Assert::assertFalse($wasAlreadyPublished);
		$databaseTransactionProducer->publish('throw-away-message');
		Assert::assertFalse($wasAlreadyPublished);
		$connection->rollBack();
		Assert::assertFalse($wasAlreadyPublished);

		$databaseTransactionProducer->publish($message);
		Assert::assertTrue($wasAlreadyPublished);
	}

	private function getConnection(): Connection
	{
		$connectionFactory = new ConnectionFactory([]);
		$connection = $connectionFactory->createConnection([
			'driver' => 'pdo_sqlite',
			'wrapperClass' => Connection::class,
		]);
		$connection->setLogger($this->createMock(LoggerInterface::class));

		return $connection;
	}

}
