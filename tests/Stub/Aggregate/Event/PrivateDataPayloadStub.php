<?php

namespace Zisato\EventSourcing\PrivateData\Tests\Stub\Aggregate\Event;

use Zisato\EventSourcing\Aggregate\Event\EventInterface;
use Zisato\EventSourcing\Aggregate\Event\PrivateData\PrivateDataPayloadInterface;

interface PrivateDataPayloadStub extends EventInterface, PrivateDataPayloadInterface
{
    
}
