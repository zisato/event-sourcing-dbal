<?php

namespace Zisato\EventSourcing\Tests\Integration\Infrastructure\Aggregate\Snapshot\Store;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Zisato\EventSourcing\Aggregate\Identity\UUID;
use Zisato\EventSourcing\Aggregate\Serializer\ReflectionAggregateRootSerializer;
use Zisato\EventSourcing\Aggregate\Snapshot\Snapshot;
use Zisato\EventSourcing\Aggregate\Snapshot\Store\SnapshotStoreInterface;
use Zisato\EventSourcing\Infrastructure\Aggregate\Snapshot\Store\DBALSnapshotStore;
use Zisato\EventSourcing\Tests\Stub\Aggregate\PhpUnitAggregateRoot;

/**
 * @covers \Zisato\EventSourcing\Infrastructure\Aggregate\Snapshot\Store\DBALSnapshotStore
 */
class DBALSnapshotStoreTest extends TestCase
{
    private Connection $connection;
    private SnapshotStoreInterface $snapshotStore;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'dbname' => getenv('DATABASE_NAME'),
            'user' => getenv('DATABASE_USERNAME'),
            'password' => getenv('DATABASE_PASSWORD'),
            'host' => getenv('DATABASE_HOST'),
            'driver' => 'pdo_mysql'
        ]);
        $serializer = new ReflectionAggregateRootSerializer();

        $this->snapshotStore = new DBALSnapshotStore($this->connection, $serializer);
    }

    protected function tearDown(): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $truncateSql = $platform->getTruncateTableSQL(DBALSnapshotStore::TABLE_NAME);

        $this->connection->executeStatement($truncateSql);
    }

    public function testSaveAndGet()
    {
        $aggregateId = UUID::generate();
        $aggregateRoot = PhpUnitAggregateRoot::create($aggregateId);
        $aggregateRoot->releaseRecordedEvents();
        $snapshot = Snapshot::create($aggregateRoot, new \DateTimeImmutable());

        $this->snapshotStore->save($snapshot);

        $result = $this->snapshotStore->get($aggregateId);

        $this->assertEquals($snapshot, $result);
    }

    public function testReturnNullWhenNoExistsSnapsot()
    {
        $aggregateId = UUID::generate();

        $snapshot = $this->snapshotStore->get($aggregateId);

        $this->assertNull($snapshot);
    }
}
