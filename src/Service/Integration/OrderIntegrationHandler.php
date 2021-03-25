<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document;

use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;

/**
 * Class OrderIntegrationHandler
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
class OrderIntegrationHandler extends IntegrationHandler
    implements IntegrationDocHandlerInterface, OrderIntegrationHandlerInterface
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
