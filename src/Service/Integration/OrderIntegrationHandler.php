<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration;

use Boxalino\DataIntegrationDoc\Service\GcpClientInterface;
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

    /**
     * @return string
     */
    public function getIntegrationStrategy(): string
    {
        return OrderIntegrationHandlerInterface::INTEGRATION_MODE;
    }

    /**
     * @return string
     */
    public function getIntegrationType(): string
    {
        return OrderIntegrationHandlerInterface::INTEGRATION_TYPE;
    }


}
