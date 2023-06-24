<?php

namespace Zisato\EventSourcing\Tests\Stub\Aggregate;

use Zisato\EventSourcing\Aggregate\AbstractAggregateRoot;
use Zisato\EventSourcing\Aggregate\Identity\UUID;
use Zisato\EventSourcing\Identity\IdentityInterface;
use Zisato\EventSourcing\Tests\Stub\Aggregate\Event\PhpUnitEvent;

class PhpUnitAggregateRoot extends AbstractAggregateRoot
{
    public static function create(IdentityInterface $id): self
    {
        $instance = new static($id);

        $instance->recordThat(PhpUnitEvent::occur($id->value()));

        return $instance;
    }
    
    public function recordPhpUnitEvent(): void
    {
        $aggregateId = UUID::generate();
        
        $this->recordThat(PhpUnitEvent::occur($aggregateId->value()));
    }

    protected function applyPhpUnitEvent(PhpUnitEvent $event): void
    {
        
    }
}
