<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Subscriber;

use Boxalino\DataIntegration\Service\Util\DiFlaggedIdHandlerInterface;
use Shopware\Core\Content\Product\Events\ProductIndexerEvent;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Coreuse Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Product subscriber
 * Adds the changed product IDs to the boxalino_di_updated_id
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate
 */
class ProductIndexerEventSubscriber implements EventSubscriberInterface
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
            ProductIndexerEvent::class => 'addUpdatedIds',
        ];
    }

    /**
     * Adding product IDs to the updated_id content
     *
     * @param ProductIndexerEvent $event
     */
    public function addUpdatedIds(ProductIndexerEvent $event): void
    {
        $ids = array_unique(array_merge($event->getIds(), $event->getChildrenIds(), $event->getParentIds()));

        try{
            $this->updatedIdRepository->flag(ProductDefinition::ENTITY_NAME, $ids);
        } catch (\Throwable $exception)
        {  }
    }


}
