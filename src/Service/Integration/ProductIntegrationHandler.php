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
 * - delta
 * - instant
 *
 * Integrated as a service
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
class ProductIntegrationHandler extends IntegrationHandler
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

    public function getIntegrationStrategy(): string
    {
        return GcpClientInterface::GCP_MODE_FULL;
    }

    public function getIntegrationType(): string
    {
        return GcpClientInterface::GCP_TYPE_PRODUCT;
    }

}
