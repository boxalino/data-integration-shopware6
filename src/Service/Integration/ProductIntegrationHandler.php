<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration;

use Boxalino\DataIntegrationDoc\Service\GcpClientInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocProductHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandler;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerTrait;
use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;

/**
 * Class ProductIntegrationHandler
 * Handles the product integration scenarios:
 * - full
 *
 * Integrated as a service
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
class ProductIntegrationHandler extends IntegrationHandler
    implements IntegrationDocHandlerInterface, ProductIntegrationHandlerInterface
{

    use IntegrationDocHandlerTrait;

    public function integrate(): void
    {
        $this->addSystemConfigurationOnHandlers();
        parent::integrate();
    }

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
        return ProductIntegrationHandlerInterface::INTEGRATION_MODE;
    }

    /**
     * @return string
     */
    public function getIntegrationType(): string
    {
        return ProductIntegrationHandlerInterface::INTEGRATION_TYPE;
    }

}
