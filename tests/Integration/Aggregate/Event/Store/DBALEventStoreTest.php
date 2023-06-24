<?php

namespace Zisato\EventSourcing\Tests\Integration\Infrastructure\Aggregate\Event\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zisato\EventSourcing\Aggregate\Event\EventInterface;
use Zisato\EventSourcing\Aggregate\Event\Serializer\EventSerializerInterface;
use Zisato\EventSourcing\Aggregate\Event\Store\EventStoreInterface;
use Zisato\EventSourcing\Aggregate\Event\Stream\EventStream;
use Zisato\EventSourcing\Infrastructure\Aggregate\Event\Store\DBALEventStore;
use Zisato\EventSourcing\Tests\Stub\Aggregate\Event\PhpUnitEvent;

class DBALEventStoreTest extends TestCase
{
    private Connection $connection;
    /** @var EventSerializerInterface|MockObject $eventSerializer */
    private $eventSerializer;
    private EventStoreInterface $eventStore;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'dbname' => getenv('DATABASE_NAME'),
            'user' => getenv('DATABASE_USERNAME'),
            'password' => getenv('DATABASE_PASSWORD'),
            'host' => getenv('DATABASE_HOST'),
            'driver' => 'pdo_mysql'
        ]);
        $this->eventSerializer = $this->createMock(EventSerializerInterface::class);

        $this->eventStore = new DBALEventStore(
            $this->connection,
            $this->eventSerializer
        );
    }

    protected function tearDown(): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $truncateSql = $platform->getTruncateTableSQL(DBALEventStore::TABLE_NAME);

        $this->connection->executeStatement($truncateSql);
    }

    public function testAppend(): void
    {
        $aggregateId = '16dfe0ec-f495-11ec-b939-0242ac120002';

        $event = $this->createEvent($aggregateId, 1);
        $eventAsArray = [
            'event_class' => \get_class($event),
            'aggregate_id' => $event->aggregateId(),
            'aggregate_version' => $event->aggregateVersion(),
            'created_at' => $event->createdAt()->format(EventSerializerInterface::DATE_FORMAT),
            'payload' => \json_encode($event->payload(), \JSON_UNESCAPED_UNICODE),
            'version' => $event->version(),
            'metadata' => \json_encode($event->metadata(), \JSON_UNESCAPED_UNICODE),
        ];
        $this->eventSerializer->expects($this->once())
            ->method('toArray')
            ->willReturn($eventAsArray);

        $this->eventStore->append($event);

        $result = $this->eventStore->exists($aggregateId);

        $this->assertTrue($result);
    }

    public function testGet(): void
    {
        $aggregateId = '16dfe0ec-f495-11ec-b939-0242ac120002';
        $aggregateVersion = 0;

        $event = $this->persistEvent($aggregateId, 1);

        $expected = EventStream::create();
        $expected->add($event);

        $this->eventSerializer->expects($this->once())
            ->method('fromArray')
            ->willReturn($event);

        $result = $this->eventStore->get($aggregateId, $aggregateVersion);

        $this->assertEquals($expected, $result);
    }

    public function testGetFromVersion(): void
    {
        $aggregateId = '16dfe0ec-f495-11ec-b939-0242ac120002';
        $aggregateVersion = 2;

        $event = $this->persistEvent($aggregateId, 3);

        $this->eventSerializer->expects($this->once())
            ->method('fromArray')
            ->willReturn($event);

        $expected = EventStream::create();
        $expected->add($event);

        $result = $this->eventStore->get($aggregateId, $aggregateVersion);

        $this->assertEquals($expected, $result);
    }

    public function testExists(): void
    {
        $aggregateId = '16dfe0ec-f495-11ec-b939-0242ac120002';

        $this->persistEvent($aggregateId, 1);

        $result = $this->eventStore->exists($aggregateId);

        $this->assertTrue($result);
    }

    public function testNotExists(): void
    {
        $aggregateId = '16dfe0ec-f495-11ec-b939-0242ac120002';

        $result = $this->eventStore->exists($aggregateId);

        $this->assertFalse($result);
    }

    private function persistEvent(string $aggregateId, int $version): EventInterface
    {
        $event = $this->createEvent($aggregateId, $version);

        $eventAsArray = [
            'event_class' => \get_class($event),
            'aggregate_id' => $event->aggregateId(),
            'aggregate_version' => $event->aggregateVersion(),
            'created_at' => $event->createdAt()->format(EventSerializerInterface::DATE_FORMAT),
            'payload' => \json_encode($event->payload(), \JSON_UNESCAPED_UNICODE),
            'version' => $event->version(),
            'metadata' => \json_encode($event->metadata(), \JSON_UNESCAPED_UNICODE),
        ];
        $this->eventSerializer->expects($this->once())
            ->method('toArray')
            ->willReturn($eventAsArray);

        $this->eventStore->append($event);

        return $event;
    }

    private function createEvent(string $aggregateId, int $version): EventInterface
    {
        return PhpUnitEvent::reconstitute(
            $aggregateId,
            $version,
            new DateTimeImmutable(),
            ['foo' => 'bar'],
            1,
            []
        );
    }
}
