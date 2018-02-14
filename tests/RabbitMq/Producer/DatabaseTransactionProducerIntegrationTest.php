<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\RabbitMq\Producer;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Psr\Log\LoggerInterface;
use VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\Connection;

class DatabaseTransactionProducerIntegrationTest extends \PHPUnit\Framework\TestCase
{

	public function testWithoutTransactionSendMessageImmediately()
	{
		$connection = $this->getConnection();

		$message1 = 'message1';
		$message2 = 'message2';
		$message1PublishCount = 0;
		$message2PublishCount = 0;

		$originalProducer = $this
			->getMockBuilder(Producer::class)
			->disableOriginalConstructor()
			->getMock();
		$originalProducer
			->expects($this->any())
			->method('publish')
			->with($this->callback(function ($receivedMessage) use ($message1, &$message1PublishCount, $message2, &$message2PublishCount) {
				if ($receivedMessage === $message1) {
					$message1PublishCount++;

					return $receivedMessage === $message1;
				}
				if ($receivedMessage === $message2) {
					$message2PublishCount++;

					return $receivedMessage === $message2;
				}
			}));

		$databaseTransactionProducer = new DatabaseTransactionProducer($originalProducer, $connection);

		$connection->query('SELECT 1');
		$this->assertSame(0, $message1PublishCount);
		$databaseTransactionProducer->publish($message1);
		$this->assertSame(1, $message1PublishCount);

		$connection->transactional(function () use (
			$connection,
			$databaseTransactionProducer,
			$message2,
			&$message2PublishCount
		) {
			$connection->query('SELECT 1');
			$this->assertSame(0, $message2PublishCount);
			$databaseTransactionProducer->publish($message2);
			$this->assertSame(0, $message2PublishCount);
		});
		$this->assertSame(1, $message2PublishCount);
		$this->assertSame(1, $message1PublishCount);
	}

	public function testMessageIsSentAfterTransaction()
	{
		$connection = $this->getConnection();

		$message = 'message';
		$wasAlreadyPublished = false;

		$originalProducer = $this
			->getMockBuilder(Producer::class)
			->disableOriginalConstructor()
			->getMock();
		$originalProducer
			->expects($this->once())
			->method('publish')
			->with($this->callback(function ($receivedMessage) use ($message, &$wasAlreadyPublished) {
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
		) {
			$connection->query('SELECT 1');
			$this->assertFalse($wasAlreadyPublished);
			$databaseTransactionProducer->publish($message);
			$this->assertFalse($wasAlreadyPublished);
		});
		$this->assertTrue($wasAlreadyPublished);
	}

	public function testMessageIsNeverSentIfTransactionIsNotCompleted()
	{
		$connection = $this->getConnection();

		$message = 'message';

		$originalProducer = $this
			->getMockBuilder(Producer::class)
			->disableOriginalConstructor()
			->getMock();
		$originalProducer
			->expects($this->never())
			->method('publish');

		$databaseTransactionProducer = new DatabaseTransactionProducer($originalProducer, $connection);

		$connection->beginTransaction();
		$connection->query('SELECT 1');
		$databaseTransactionProducer->publish($message);
	}

	public function testMessageIsThrownAwayIfTransactionWasRolledBack()
	{
		$connection = $this->getConnection();

		$message = 'message';
		$wasAlreadyPublished = false;

		$originalProducer = $this
			->getMockBuilder(Producer::class)
			->disableOriginalConstructor()
			->getMock();
		$originalProducer
			->expects($this->once())
			->method('publish')
			->with($this->callback(function ($receivedMessage) use ($message, &$wasAlreadyPublished) {
				$wasAlreadyPublished = true;

				return $receivedMessage === $message;
			}));

		$databaseTransactionProducer = new DatabaseTransactionProducer($originalProducer, $connection);

		$connection->beginTransaction();
		$connection->query('SELECT 1');
		$this->assertFalse($wasAlreadyPublished);
		$databaseTransactionProducer->publish('throw-away-message');
		$this->assertFalse($wasAlreadyPublished);
		$connection->rollBack();
		$this->assertFalse($wasAlreadyPublished);

		$databaseTransactionProducer->publish($message);
		$this->assertTrue($wasAlreadyPublished);
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
