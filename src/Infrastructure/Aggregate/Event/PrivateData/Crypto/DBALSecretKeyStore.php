<?php

declare(strict_types=1);

namespace Zisato\EventSourcing\Infrastructure\Aggregate\Event\PrivateData\Crypto;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Crypto\SecretKeyStoreInterface;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\ValueObject\SecretKey;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Exception\DeletedKeyException;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Exception\KeyNotFoundException;

final class DBALSecretKeyStore implements SecretKeyStoreInterface
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'key_store';

    /**
     * @var string
     */
    public const TABLE_SCHEMA = 'CREATE TABLE `' . self::TABLE_NAME . '` (
    `aggregate_id` VARCHAR(36) NOT NULL COLLATE utf8mb4_general_ci,
    `secret_key` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME(6) NOT NULL,
    PRIMARY KEY (`aggregate_id`) USING BTREE,
    INDEX `key_created_at_idx` (`created_at`) USING BTREE
)
COLLATE=utf8mb4_general_ci
ENGINE=InnoDB;
';

    /**
     * @var string
     */
    private const SQL_FORGET = 'UPDATE `' . self::TABLE_NAME . '` 
    SET `secret_key` = ""
    WHERE (`aggregate_id` = :aggregate_id)
';

    /**
     * @var string
     */
    private const SQL_GET = 'SELECT * 
    FROM `' . self::TABLE_NAME . '`
    WHERE (`aggregate_id` = :aggregate_id)
';

    public function __construct(private readonly Connection $connection) {}

    public function save(string $aggregateId, SecretKey $key): void
    {
        $createdAt = new \DateTime();

        $this->connection->insert(
            static::TABLE_NAME,
            [
                'aggregate_id' => $aggregateId,
                'secret_key' => $key->value(),
                'created_at' => $createdAt,
            ],
            [
                'aggregate_id' => Types::STRING,
                'secret_key' => Types::STRING,
                'created_at' => Types::DATETIME_MUTABLE,
            ]
        );
    }

    public function get(string $aggregateId): SecretKey
    {
        $result = $this->connection->fetchAssociative(
            self::SQL_GET,
            [
                'aggregate_id' => $aggregateId,
            ],
            [
                'aggregate_id' => Types::STRING,
            ]
        );

        if ($result === false) {
            throw new KeyNotFoundException();
        }

        $key = $result['secret_key'];

        if ($key === '') {
            throw new DeletedKeyException();
        }

        return SecretKey::create($key);
    }

    public function forget(string $aggregateId): void
    {
        $this->connection->executeQuery(
            self::SQL_FORGET,
            [
                'aggregate_id' => $aggregateId,
            ],
            [
                'aggregate_id' => Types::STRING,
            ]
        );
    }
}
