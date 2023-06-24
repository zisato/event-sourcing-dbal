<?php

namespace Zisato\EventSourcing\Tests\Integration\Aggregate\Event\PrivateData\Crypto;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Crypto\SecretKeyStoreInterface;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Exception\DeletedKeyException;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\Exception\KeyNotFoundException;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\ValueObject\SecretKey;
use Zisato\EventSourcing\Infrastructure\Aggregate\Event\PrivateData\Crypto\DBALSecretKeyStore;

class DBALSecretKeyStoreTest extends TestCase
{
    private Connection $connection;
    private SecretKeyStoreInterface $secretkeyStore;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'dbname' => getenv('DATABASE_NAME'),
            'user' => getenv('DATABASE_USERNAME'),
            'password' => getenv('DATABASE_PASSWORD'),
            'host' => getenv('DATABASE_HOST'),
            'driver' => 'pdo_mysql'
        ]);

        $this->secretkeyStore = new DBALSecretKeyStore($this->connection);
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
        $key = SecretKey::create('MyAwesomeSecretKey');

        $this->secretkeyStore->save($aggregateId, $key);

        $result = $this->secretkeyStore->get($aggregateId);
        
        $this->assertEquals($result, $key);
    }

    public function testGetKeyNotFoundException(): void
    {
        $this->expectException(KeyNotFoundException::class);

        $aggregateId = '022390a2-f596-11ec-b939-0242ac120002';

        $this->secretkeyStore->get($aggregateId);
    }

    public function testForgetAndGetDeletedKeyException(): void
    {
        $this->expectException(DeletedKeyException::class);
        
        $aggregateId = '022390a2-f596-11ec-b939-0242ac120002';
        $key = SecretKey::create('MyAwesomeSecretKey');

        $this->secretkeyStore->save($aggregateId, $key);

        $this->secretkeyStore->forget($aggregateId);

        $this->secretkeyStore->get($aggregateId);
    }
}
