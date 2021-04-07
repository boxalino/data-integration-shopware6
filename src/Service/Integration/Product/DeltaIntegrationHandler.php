<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Product;

use Boxalino\DataIntegrationDoc\Service\GcpClientInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocProductHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductDeltaIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;

/**
 * Class DeltaIntegrationHandler
 * Handles the product integration scenarios:
 * - delta
 *
 * Integrated as a service
 *
 * @package Boxalino\DataIntegrationDoc\Service\Integration\Product
 */
class DeltaIntegrationHandler extends IntegrationHandler
    implements IntegrationDocHandlerInterface, ProductDeltaIntegrationHandlerInterface
{

    use IntegrationDocHandlerTrait;

    /**
     * @return \ArrayIterator
     */
    public function getDocs(): \ArrayIterator
    {
        $this->addSystemConfigurationOnHandlers();
        return parent::getDocs();
    }

    /**
     * @return string
     */
    public function getIntegrationMode(): string
    {
        return ProductDeltaIntegrationHandlerInterface::INTEGRATION_MODE;
    }

    /**
     * @return string
     */
    public function getIntegrationType(): string
    {
        return ProductDeltaIntegrationHandlerInterface::INTEGRATION_TYPE;
    }

}
