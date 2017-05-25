<?php

declare(strict_types = 1);

namespace VasekPurchart\RabbitMqDatabaseTransactionProducerBundle\Doctrine\Connection;

use Doctrine\DBAL\Driver\PDOSqlite\Driver as PDOSqliteDriver;

class ConnectionTest extends \PHPUnit\Framework\TestCase
{

	public function testSingleCallbackIsCalledOnlyOnce()
	{
		$connection = $this->getConnection();

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->once())
			->method('callback1');

		$connection->addAfterCommitCallback(function () use ($mock) {
			$mock->callback1();
		});

		// callbacks should be called
		$connection->beginTransaction();
		$connection->commit();

		// callbacks should not be called again
		$connection->beginTransaction();
		$connection->commit();
	}

	public function testSingleCallbackIsNotCalledAfterRollback()
	{
		$connection = $this->getConnection();

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->never())
			->method('callback1');

		$connection->addAfterCommitCallback(function () use ($mock) {
			$mock->callback1();
		});

		// callbacks should be cleared
		$connection->beginTransaction();
		$connection->rollBack();

		// callbacks should be already cleared
		$connection->beginTransaction();
		$connection->commit();
	}

	public function testMultipleCallbacksAreCalled()
	{
		$connection = $this->getConnection();

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->once())
			->method('callback1');
		$mock
			->expects($this->once())
			->method('callback2');

		$connection->addAfterCommitCallback(function () use ($mock) {
			$mock->callback1();
		});
		$connection->addAfterCommitCallback(function () use ($mock) {
			$mock->callback2();
		});

		// callbacks should be called
		$connection->beginTransaction();
		$connection->commit();

		// callbacks should not be called again
		$connection->beginTransaction();
		$connection->commit();
	}

	public function testMultipleCallbacksAreNotCalledAfterRollback()
	{
		$connection = $this->getConnection();

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->never())
			->method('callback1');
		$mock
			->expects($this->never())
			->method('callback2');

		$connection->addAfterCommitCallback(function () use ($mock) {
			$mock->callback1();
		});
		$connection->addAfterCommitCallback(function () use ($mock) {
			$mock->callback2();
		});

		// callbacks should be cleared
		$connection->beginTransaction();
		$connection->rollBack();

		// callbacks should be already cleared
		$connection->beginTransaction();
		$connection->commit();
	}

	public function testFailedNestedTransactionClearsAfterCommitCallbacks()
	{
		$connection = $this->getConnection();

		$mock = $this->getCallbacksMock();
		$mock
			->expects($this->never())
			->method('callback1');

		$connection->addAfterCommitCallback(function () use ($mock) {
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

}
