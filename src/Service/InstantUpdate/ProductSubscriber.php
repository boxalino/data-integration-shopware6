<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate;

use Shopware\Core\Content\Product\Events\ProductIndexerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Boxalino\DataIntegration\Service\InstantUpdateHandlerInterface;

/**
 * Class ProductSubscriber
 * Event to trigger real-time data index for the component (product)
 *
 * It is integrated & defined in the integration layer of a Boxalino project setup
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate
 */
class ProductSubscriber implements EventSubscriberInterface
{
    /**
     * @var InstantUpdateHandlerInterface
     */
    private $handler;

    public function __construct(InstantUpdateHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    public static function getSubscribedEvents()
    {
        return [
            ProductIndexerEvent::class => 'update',
        ];
    }

    public function update(ProductIndexerEvent $event): void
    {
        $ids = array_unique(array_merge($event->getIds(), $event->getChildrenIds(), $event->getParentIds()));
        $this->handler->handle($ids);
    }

}
