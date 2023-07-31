<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Subscriber;

use Boxalino\DataIntegration\Service\Util\DiFlaggedIdHandlerInterface;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
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
     * @var DiFlaggedIdHandlerInterface
     */
    protected $updatedIdRepository;

    public function __construct(
        DiFlaggedIdHandlerInterface $updatedIdRepository
    ){
        $this->updatedIdRepository = $updatedIdRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            StateMachineTransitionEvent::class => 'addUpdatedIdsFromStateChanged',
            OrderEvents::ORDER_LINE_ITEM_WRITTEN_EVENT => 'addUpdatedIds',
            OrderEvents::ORDER_LINE_ITEM_DELETED_EVENT => 'addUpdatedIds',
        ];
    }

    /**
     * Adding order IDs to the updated_id content
     *
     * @param EntityWrittenEvent $event
     */
    public function addUpdatedIds(EntityWrittenEvent $event): void
    {
        $ids = []; $salesChannelIds=[];
        foreach ($event->getWriteResults() as $result)
        {
            if ($result->hasPayload('orderId'))
            {
                $ids[] = $result->getProperty('orderId');
            }
        }

        if(empty($ids))
        {
            return;
        }

        try{
            $this->updatedIdRepository->flag(OrderDefinition::ENTITY_NAME, array_unique($ids), $salesChannelIds);
        } catch (\Throwable $exception)
        {  }
    }

    /**
     * Mark the order that was placed
     *
     * DISCLAIMER: for the export logic - when using the created_at / updated_at as conditional for delta, -
     * - listening to this event is pointless
     *
     * @deprecated
     * @param CheckoutOrderPlacedEvent $event
     */
    public function addUpdatedIdFromOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        try{
            $this->updatedIdRepository->flag(OrderDefinition::ENTITY_NAME, [$event->getOrder()->getId()], [$event->getSalesChannelId()]);
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
                try{
                    $this->updatedIdRepository->flag(OrderDefinition::ENTITY_NAME, [$event->getEntityId()]);
                } catch (\Throwable $exception)
                {  }
            }
        }
    }


}
