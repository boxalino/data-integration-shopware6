<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Subscriber;

use Boxalino\DataIntegration\Console\Mode\InstantTrait;
use Boxalino\DataIntegration\Console\Type\ProductTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegration\Subscriber\InstantDiSubscriber;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductInstantIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\Events\ProductIndexerEvent;

/**
 * Product instant update subscriber
 * Updates the changed product IDs
 *
 * It is used to update elements (status, properties, etc)
 * It does not remove existing items
 * It does not add new items
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate
 */
class ProductDataIntegration extends InstantDiSubscriber
{
    use InstantTrait;
    use ProductTrait;

    /**
     * @var ProductInstantIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        ProductInstantIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager);
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
        $this->handle($ids);
    }


}
