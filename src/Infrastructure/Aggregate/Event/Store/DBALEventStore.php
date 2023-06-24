<?php

declare(strict_types=1);

namespace Zisato\EventSourcing\Infrastructure\Aggregate\Event\Store;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Zisato\EventSourcing\Aggregate\Event\EventInterface;
use Zisato\EventSourcing\Aggregate\Event\Serializer\EventSerializerInterface;
use Zisato\EventSourcing\Aggregate\Event\Store\EventStoreInterface;
use Zisato\EventSourcing\Aggregate\Event\Stream\EventStream;
use Zisato\EventSourcing\Aggregate\Event\Stream\EventStreamInterface;

final class DBALEventStore implements EventStoreInterface
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'event_store';

    /**
     * @var string
     */
    public const TABLE_SCHEMA = 'CREATE TABLE `' . self::TABLE_NAME . '` (
    `aggregate_id` VARCHAR(36) NOT NULL COLLATE utf8mb4_general_ci,
    `aggregate_version` INT(10) UNSIGNED NOT NULL,
    `event_class` VARCHAR(255) NOT NULL COLLATE utf8mb4_general_ci,
    `payload` LONGTEXT NOT NULL COLLATE utf8mb4_general_ci,
    `metadata` LONGTEXT NOT NULL COLLATE utf8mb4_general_ci,
    `version` INT(10) UNSIGNED NOT NULL,
    `created_at` DATETIME(6) NOT NULL,
    PRIMARY KEY (`aggregate_id`, `aggregate_version`) USING BTREE,
    INDEX `event_created_at_idx` (`created_at`) USING BTREE
)
COLLATE=utf8mb4_general_ci
ENGINE=InnoDB;
';

    /**
     * @var string
     */
    private const SQL_EXISTS = 'SELECT `aggregate_id`
    FROM `' . self::TABLE_NAME . '`
    WHERE `aggregate_id` = :aggregate_id
    LIMIT 1
';

    /**
     * @var string
     */
    private const SQL_GET = 'SELECT * 
    FROM `' . self::TABLE_NAME . '`
    WHERE (`aggregate_id` = :aggregate_id) 
    AND (`aggregate_version` > :aggregate_version) 
    ORDER BY `aggregate_version` ASC
';

    public function __construct(private readonly Connection $connection, private readonly EventSerializerInterface $eventSerializer) {}

    public function exists(string $aggregateId): bool
    {
        $row = $this->connection->fetchAssociative(
            self::SQL_EXISTS,
            [
                'aggregate_id' => $aggregateId,
            ],
            [
                'aggregate_id' => Types::STRING,
            ]
        );

        return $row !== false;
    }

    public function get(string $aggregateId, int $fromAggregateVersion): EventStreamInterface
    {
        $results = $this->connection->fetchAllAssociative(
            self::SQL_GET,
            [
                'aggregate_id' => $aggregateId,
                'aggregate_version' => $fromAggregateVersion,
            ],
            [
                'aggregate_id' => Types::STRING,
                'aggregate_version' => Types::INTEGER,
            ]
        );

        $eventStream = EventStream::create();

        foreach ($results as $data) {
            $event = $this->eventSerializer->fromArray($data);

            $eventStream->add($event);
        }

        return $eventStream;
    }

    public function append(EventInterface $event): void
    {
        $data = $this->eventSerializer->toArray($event);

        $this->connection->insert(self::TABLE_NAME, $data);
    }
}
