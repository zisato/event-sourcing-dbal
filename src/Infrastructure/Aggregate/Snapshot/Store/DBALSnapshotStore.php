<?php

declare(strict_types=1);

namespace Zisato\EventSourcing\Infrastructure\Aggregate\Snapshot\Store;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use InvalidArgumentException;
use Zisato\EventSourcing\Aggregate\Serializer\AggregateRootSerializerInterface;
use Zisato\EventSourcing\Aggregate\Snapshot\Snapshot;
use Zisato\EventSourcing\Aggregate\Snapshot\SnapshotInterface;
use Zisato\EventSourcing\Aggregate\Snapshot\Store\SnapshotStoreInterface;
use Zisato\EventSourcing\Identity\IdentityInterface;

final class DBALSnapshotStore implements SnapshotStoreInterface
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'snapshot_store';

    /**
     * @var string
     */
    public const TABLE_SCHEMA = 'CREATE TABLE `' . self::TABLE_NAME . '` (
        `aggregate_id` VARCHAR(36) NOT NULL COLLATE utf8mb4_general_ci,
        `aggregate_version` INT(10) UNSIGNED NOT NULL,
        `aggregate_class_name` VARCHAR(255) NOT NULL COLLATE utf8mb4_general_ci,
        `data` LONGTEXT NOT NULL COLLATE utf8mb4_general_ci,
        `created_at` DATETIME(6) NOT NULL,
        INDEX `aggregate_id_idx` (`aggregate_id`) USING BTREE,
        INDEX `aggregate_version_idx` (`aggregate_version`) USING BTREE
    )
    COLLATE=utf8mb4_general_ci
    ENGINE=InnoDB;
    ';

    /**
     * @var string
     */
    private const DATE_FORMAT = 'Y-m-d H:i:s.u';

    /**
     * @var string
     */
    private const SQL_GET = 'SELECT s.data, s.created_at
        FROM snapshot_store s 
        WHERE (aggregate_id = :aggregate_id) 
        ORDER BY aggregate_version DESC
    ';

    public function __construct(private readonly Connection $connection, private readonly AggregateRootSerializerInterface $serializer) {}

    public function get(IdentityInterface $aggregateId): ?SnapshotInterface
    {
        $snapshot = $this->connection->fetchAssociative(
            self::SQL_GET,
            [
                'aggregate_id' => $aggregateId->value(),
            ],
            [
                'aggregate_id' => Types::STRING,
            ]
        );

        if ($snapshot === false) {
            return null;
        }

        $createdAt = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $snapshot['created_at']);

        if ($createdAt === false) {
            throw new InvalidArgumentException(sprintf(
                'Invalid snapshot created_at date: %s',
                $snapshot['created_at']
            ));
        }

        return Snapshot::create($this->serializer->deserialize($snapshot['data']), $createdAt);
    }

    public function save(SnapshotInterface $snapshot): void
    {
        $this->connection->insert(self::TABLE_NAME, $this->createData($snapshot));
    }

    /**
     * @return array{aggregate_id: string, aggregate_version: int, aggregate_class_name: string, data: string, created_at: string}
     */
    private function createData(SnapshotInterface $snapshot): array
    {
        $aggregateRoot = $snapshot->aggregateRoot();

        return [
            'aggregate_id' => $aggregateRoot->id()
                ->value(),
            'aggregate_version' => $aggregateRoot->version()
                ->value(),
            'aggregate_class_name' => $snapshot->aggregateRootClassName(),
            'data' => $this->serializer->serialize($aggregateRoot),
            'created_at' => $snapshot->createdAt()
                ->format(self::DATE_FORMAT),
        ];
    }
}
