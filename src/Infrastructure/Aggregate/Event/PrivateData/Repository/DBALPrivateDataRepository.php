<?php

declare(strict_types=1);

namespace Zisato\EventSourcing\Infrastructure\Aggregate\Event\PrivateData\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Exception\ForgottedPrivateDataException;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Repository\PrivateDataRepositoryInterface;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Serializer\PayloadValueSerializerInterface;
use Zisato\EventSourcing\Identity\IdentityInterface;

final class DBALPrivateDataRepository implements PrivateDataRepositoryInterface
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'private_data';
    
    /**
     * @var string
     */
    public const TABLE_SCHEMA = 'CREATE TABLE `' . self::TABLE_NAME . '` (
    `aggregate_id` VARCHAR(36) NOT NULL COLLATE utf8mb4_general_ci,
    `value_id` VARCHAR(36) NOT NULL COLLATE utf8mb4_general_ci,
    `value` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`aggregate_id`, `value_id`) USING BTREE
)
COLLATE=utf8mb4_general_ci
ENGINE=InnoDB;
';
    
    /**
     * @var string
     */
    private const SQL_GET = 'SELECT * 
FROM `' . self::TABLE_NAME . '`
WHERE (`aggregate_id` = :aggregate_id)
AND (`value_id` = :value_id)
';

    public function __construct(private readonly Connection $connection, private readonly PayloadValueSerializerInterface $payloadValueSerializer) {}

    /**
     * @return mixed
     */
    public function get(string $aggregateId, IdentityInterface $valueId)
    {
        $result = $this->connection->fetchAssociative(
            self::SQL_GET,
            [
                'aggregate_id' => $aggregateId,
                'value_id' => $valueId->value(),
            ],
            [
                'aggregate_id' => Types::STRING,
                'value_id' => Types::STRING,
            ]
        );

        if ($result === false) {
            throw new ForgottedPrivateDataException();
        }

        return $this->payloadValueSerializer->fromString($result['value']);
    }

    /**
     * @param mixed $value
     */
    public function save(string $aggregateId, IdentityInterface $valueId, $value): void
    {
        $this->connection->insert(
            self::TABLE_NAME,
            [
                'aggregate_id' => $aggregateId,
                'value_id' => $valueId->value(),
                'value' => $this->payloadValueSerializer->toString($value),
            ],
            [
                'aggregate_id' => Types::STRING,
                'value_id' => Types::STRING,
                'value' => Types::TEXT,
            ]
        );
    }
}
