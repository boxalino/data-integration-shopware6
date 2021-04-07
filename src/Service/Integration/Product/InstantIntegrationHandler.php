<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Product;

use Boxalino\DataIntegrationDoc\Service\GcpClientInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocProductHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductInstantIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;

/**
 * Class InstantIntegrationHandler
 * Handles the product integration scenarios:
 * - instant
 *
 * Integrated as a service
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
class InstantIntegrationHandler extends IntegrationHandler
    implements IntegrationDocHandlerInterface, ProductInstantIntegrationHandlerInterface
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
        return ProductInstantIntegrationHandlerInterface::INTEGRATION_MODE;
    }

    /**
     * @return string
     */
    public function getIntegrationType(): string
    {
        return ProductInstantIntegrationHandlerInterface::INTEGRATION_TYPE;
    }

}
