<?php

namespace Zisato\EventSourcing\Tests\Unit\Infrastructure\Aggregate\Snapshot\Store;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zisato\EventSourcing\Aggregate\Identity\UUID;
use Zisato\EventSourcing\Aggregate\Serializer\AggregateRootSerializerInterface;
use Zisato\EventSourcing\Infrastructure\Aggregate\Snapshot\Store\DBALSnapshotStore;

/**
 * @covers \Zisato\EventSourcing\Infrastructure\Aggregate\Snapshot\Store\DBALSnapshotStore
 */
class DBALSnapshotStoreTest extends TestCase
{
    /** @var MockObject|Connection $connection */
    private $connection;
    /** @var MockObject|AggregateRootSerializerInterface $serializer */
    private $serializer;
    private $snapshotStore;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->serializer = $this->createMock(AggregateRootSerializerInterface::class);

        $this->snapshotStore = new DBALSnapshotStore($this->connection, $this->serializer);
    }

    public function testThrowInvalidArgumentExceptionWhenInvalidCreatedAtOnGet(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $aggregateId = UUID::generate();
        $snapshot = ['created_at' => 'invalidDate'];

        $this->connection->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn($snapshot);


        $this->snapshotStore->get($aggregateId);
    }
}
