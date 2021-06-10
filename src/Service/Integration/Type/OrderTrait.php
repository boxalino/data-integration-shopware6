<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Type;

use Boxalino\DataIntegrationDoc\Service\GcpRequestInterface;
use Shopware\Core\Checkout\Order\OrderDefinition;

/**
 * Class OrderTrait
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
trait OrderTrait
{

    /**
     * @return string
     */
    public function getIntegrationType(): string
    {
        return GcpRequestInterface::GCP_TYPE_ORDER;
    }

    /**
     * @return string
     */
    public function getEntityName() : string
    {
        return OrderDefinition::ENTITY_NAME;
    }

}
