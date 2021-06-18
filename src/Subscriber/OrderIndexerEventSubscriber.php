<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Order subscriber
 * Adds the changed order IDs to the boxalino_di_updated_id
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate
 */
class OrderIndexerEventSubscriber implements EventSubscriberInterface
{

    /**
     * @var EntityRepositoryInterface
     */
    protected $updatedIdRepository;

    public function __construct(
        EntityRepositoryInterface $updatedIdRepository
    ){
        $this->updatedIdRepository = $updatedIdRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            CheckoutOrderPlacedEvent::class => 'addUpdatedIdFromOrderPlaced',
            StateMachineTransitionEvent::class => 'addUpdatedIdsFromStateChanged',
            OrderEvents::ORDER_LINE_ITEM_WRITTEN_EVENT => 'addUpdatedIds',
            OrderEvents::ORDER_LINE_ITEM_DELETED_EVENT => 'addUpdatedIds',
        ];
    }

    /**
     * Adding customer IDs to the updated_id content
     *
     * @param EntityWrittenEvent $event
     */
    public function addUpdatedIds(EntityWrittenEvent $event): void
    {
        $ids = [];
        foreach ($event->getWriteResults() as $result)
        {
            if ($result->hasPayload('orderId'))
            {
                $ids[] = $result->getProperty('orderId');
            }
        }

        $content = [];
        foreach(array_unique($ids) as $id)
        {
            $content[] = ["entityName" => OrderDefinition::ENTITY_NAME, "entityId"=> $id, 'id' => Uuid::randomHex()];
        }

        try{
            $this->updatedIdRepository->create($content, $event->getContext());
        } catch (\Throwable $exception)
        {  }
    }

    /**
     * Mark the order that was placed
     *
     * @param CheckoutOrderPlacedEvent $event
     */
    public function addUpdatedIdFromOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $content[] = ["entityName" => OrderDefinition::ENTITY_NAME, "entityId"=> $event->getOrder()->getId(), 'id' => Uuid::randomHex()];

        try{
            $this->updatedIdRepository->create($content, $event->getContext());
        } catch (\Throwable $exception)
        {  }
    }

    /**
     * Mark the order that has it`s transition changed
     *
     * @param StateMachineTransitionEvent $event
     */
    public function addUpdatedIdsFromStateChanged(StateMachineTransitionEvent $event): void
    {
        if ($event->getContext()->getVersionId() == Defaults::LIVE_VERSION)
        {
            if ($event->getEntityName() === OrderDefinition::ENTITY_NAME)
            {
                $content[] = ["entityName" => OrderDefinition::ENTITY_NAME, "entityId"=> $event->getEntityId(), 'id' => Uuid::randomHex()];

                try{
                    $this->updatedIdRepository->create($content, $event->getContext());
                } catch (\Throwable $exception)
                {  }
            }
        }
    }


}
