<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Subscriber;

use Boxalino\DataIntegration\Service\Util\DiFlaggedIdHandlerInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Customer\Event\CustomerIndexerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * User subscriber
 * Adds the changed user IDs to the boxalino_di_updated_id
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate
 */
class UserIndexerEventSubscriber implements EventSubscriberInterface
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
            CustomerIndexerEvent::class => 'addUpdatedIds',
        ];
    }

    /**
     * Adding customer IDs to the updated_id content
     *
     * @param CustomerIndexerEvent $event
     */
    public function addUpdatedIds(CustomerIndexerEvent $event): void
    {
        try{
            $this->updatedIdRepository->flag(CustomerDefinition::ENTITY_NAME, $event->getIds());
        } catch (\Throwable $exception)
        {  }
    }


}
