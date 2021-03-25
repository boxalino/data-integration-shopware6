<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document;

use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;

/**
 * Class IntegrationDocHandler
 * Integration handler for the instant update process
 * creates the docs per each document type required for the process
 * (declared as service)
 */
class IntegrationDocHandler extends IntegrationHandler
    implements IntegrationDocHandlerInterface, ProductIntegrationHandlerInterface
{

    use IntegrationDocHandlerTrait;

    /**
     * @return \ArrayIterator
     */
    public function getDocs(): \ArrayIterator
    {
        $this->addPropertiesOnHandlers();
        return parent::getDocs();
    }

}
