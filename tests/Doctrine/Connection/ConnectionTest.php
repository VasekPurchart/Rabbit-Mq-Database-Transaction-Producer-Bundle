<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use Doctrine\DBAL\Driver\PDOSqlite\Driver as PDOSqliteDriver;
use Psr\Log\LoggerInterface;

class ConnectionTest extends \PHPUnit\Framework\TestCase
{

	public function testSingleCallbackIsCalledOnlyOnce(): void
	{
		$connection = $this->getConnection();

		$loggerMock = $this->getLoggerMock();
		$loggerMock
			->expects($this->never())
			->method('error');
		$connection->setLogger($loggerMock);

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->once())
			->method('callback1');

		$connection->addAfterCommitCallback(function () use ($mock): void {
			$mock->callback1();
		});

		// callbacks should be called
		$connection->beginTransaction();
		$connection->commit();

		// callbacks should not be called again
		$connection->beginTransaction();
		$connection->commit();
	}

	public function testSingleCallbackIsNotCalledAfterRollback(): void
	{
		$connection = $this->getConnection();

		$loggerMock = $this->getLoggerMock();
		$loggerMock
			->expects($this->never())
			->method('error');
		$connection->setLogger($loggerMock);

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->never())
			->method('callback1');

		$connection->addAfterCommitCallback(function () use ($mock): void {
			$mock->callback1();
		});

		// callbacks should be cleared
		$connection->beginTransaction();
		$connection->rollBack();

		// callbacks should be already cleared
		$connection->beginTransaction();
		$connection->commit();
	}

	public function testMultipleCallbacksAreCalled(): void
	{
		$connection = $this->getConnection();

		$loggerMock = $this->getLoggerMock();
		$loggerMock
			->expects($this->never())
			->method('error');
		$connection->setLogger($loggerMock);

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->once())
			->method('callback1');
		$mock
			->expects($this->once())
			->method('callback2');

		$connection->addAfterCommitCallback(function () use ($mock): void {
			$mock->callback1();
		});
		$connection->addAfterCommitCallback(function () use ($mock): void {
			$mock->callback2();
		});

		// callbacks should be called
		$connection->beginTransaction();
		$connection->commit();

		// callbacks should not be called again
		$connection->beginTransaction();
		$connection->commit();
	}

	public function testMultipleCallbacksAreNotCalledAfterRollback(): void
	{
		$connection = $this->getConnection();

		$loggerMock = $this->getLoggerMock();
		$loggerMock
			->expects($this->never())
			->method('error');
		$connection->setLogger($loggerMock);

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->never())
			->method('callback1');
		$mock
			->expects($this->never())
			->method('callback2');

		$connection->addAfterCommitCallback(function () use ($mock): void {
			$mock->callback1();
		});
		$connection->addAfterCommitCallback(function () use ($mock): void {
			$mock->callback2();
		});

		// callbacks should be cleared
		$connection->beginTransaction();
		$connection->rollBack();

		// callbacks should be already cleared
		$connection->beginTransaction();
		$connection->commit();
	}

	public function testFailedNestedTransactionClearsAfterCommitCallbacks(): void
	{
		$connection = $this->getConnection();

		$loggerMock = $this->getLoggerMock();
		$loggerMock
			->expects($this->never())
			->method('error');
		$connection->setLogger($loggerMock);

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->never())
			->method('callback1');

		$connection->addAfterCommitCallback(function () use ($mock): void {
			$mock->callback1();
		});

		// outer transaction
		$connection->beginTransaction();

		// inner transaction
		$connection->beginTransaction();
		$connection->rollBack();

		$this->assertTrue($connection->isRollbackOnly());

		// outer transaction
		$connection->rollBack();

		// callbacks should be already cleared
		$connection->beginTransaction();
		$this->assertFalse($connection->isRollbackOnly());
		$connection->commit();
	}

	public function testExceptionInCallbackIsProperlyHandled(): void
	{
		$connection = $this->getConnection();

		$loggerMock = $this->getLoggerMock();
		$loggerMock
			->expects($this->once())
			->method('error')
			->with(
				$this->callback(function ($message): bool {
					return strpos($message, 'callback failed') !== false;
				}),
				$this->callback(function ($data): bool {
					return ($data['exception'] instanceof \Exception)
						&& $data['exception']->getMessage() === 'callback failed';
				})
			);
		$connection->setLogger($loggerMock);

		$connection->addAfterCommitCallback(function (): void {
			throw new \Exception('callback failed');
		});

		$connection->beginTransaction();
		$connection->commit();

		$this->assertTrue(true, 'callback exception was properly caught');
	}

	public function testMissingLogger(): void
	{
		$connection = $this->getConnection();

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->never())
			->method('callback1');

		$this->expectException(
			\VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\ConnectionRequiresLoggerException::class
		);

		$connection->addAfterCommitCallback(function () use ($mock): void {
			$mock->callback1();
		});

		$this->fail();
	}

	public function testSetLoggerOnlyOnce(): void
	{
		$connection = $this->getConnection();

		$loggerMock = $this->getLoggerMock();
		$loggerMock
			->expects($this->never())
			->method('error');
		$connection->setLogger($loggerMock);

		$this->expectException(
			\VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\ConnectionLoggerAlreadyInitializedException::class
		);

		$connection->setLogger($loggerMock);
	}

	private function getConnection(): Connection
	{
		return new Connection(['path' => ':memory:'], new PDOSqliteDriver());
	}

	/**
	 * @return \VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection\DummyCallbacks|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getCallbacksMock(): DummyCallbacks
	{
		return $this->createMock(DummyCallbacks::class);
	}

	/**
	 * @return \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	private function getLoggerMock(): LoggerInterface
	{
		return $this->createMock(LoggerInterface::class);
	}

}
