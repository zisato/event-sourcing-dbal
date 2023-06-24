<?php

namespace Zisato\EventSourcing\Tests\Integration\Aggregate\Event\PrivateData\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Crypto\SecretKeyStoreInterface;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Exception\DeletedKeyException;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Exception\ForgottedPrivateDataException;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Exception\KeyNotFoundException;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Repository\PrivateDataRepositoryInterface;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Serializer\JsonPayloadValueSerializer;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Serializer\PayloadValueSerializerInterface;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\ValueObject\SecretKey;
use Zisato\EventSourcing\Aggregate\Identity\UUID;
use Zisato\EventSourcing\Infrastructure\Aggregate\Event\PrivateData\Crypto\DBALSecretKeyStore;
use Zisato\EventSourcing\Infrastructure\Aggregate\Event\PrivateData\Repository\DBALPrivateDataRepository;

class DBALPrivateDataRepositoryTest extends TestCase
{
    private PrivateDataRepositoryInterface $repository;
    private Connection $connection;
    private PayloadValueSerializerInterface $payloadValueSerializer;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'dbname' => getenv('DATABASE_NAME'),
            'user' => getenv('DATABASE_USERNAME'),
            'password' => getenv('DATABASE_PASSWORD'),
            'host' => getenv('DATABASE_HOST'),
            'driver' => 'pdo_mysql'
        ]);
        $this->payloadValueSerializer = new JsonPayloadValueSerializer();

        $this->repository = new DBALPrivateDataRepository($this->connection, $this->payloadValueSerializer);
    }

    protected function tearDown(): void
    {
        $platform = $this->connection->getDatabasePlatform();
        $truncateSql = $platform->getTruncateTableSQL(DBALSecretKeyStore::TABLE_NAME);

        $this->connection->executeStatement($truncateSql);
    }

    public function testSaveAndGetSuccessfully(): void
    {
        $aggregateId = '022390a2-f596-11ec-b939-0242ac120002';
        $valueId = UUID::generate();
        $value = 'foo';

        $this->repository->save($aggregateId, $valueId, $value);

        $result = $this->repository->get($aggregateId, $valueId);
        
        $this->assertEquals($result, 'foo');
    }

    public function testShouldThrowForgottedPrivateDataException(): void
    {
        $this->expectException(ForgottedPrivateDataException::class);

        $aggregateId = '022390a2-f596-11ec-b939-0242ac120002';
        $valueId = UUID::generate();

        $this->repository->get($aggregateId, $valueId);
    }
}
