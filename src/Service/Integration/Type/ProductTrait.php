<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Type;

use Boxalino\DataIntegrationDoc\Service\GcpRequestInterface;
use Shopware\Core\Content\Product\ProductDefinition;

/**
 * Class IntegrationTypeTrait
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
trait ProductTrait
{

    /**
     * @return string
     */
    public function getIntegrationType(): string
    {
        return GcpRequestInterface::GCP_TYPE_PRODUCT;
    }

    /**
     * @return string
     */
    public function getEntityName() : string
    {
        return ProductDefinition::ENTITY_NAME;
    }


}
