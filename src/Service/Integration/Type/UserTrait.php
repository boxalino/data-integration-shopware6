<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Type;

use Boxalino\DataIntegrationDoc\Service\GcpRequestInterface;
use Shopware\Core\Checkout\Customer\CustomerDefinition;

/**
 * Class IntegrationTypeTrait
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
trait UserTrait
{

    /**
     * @return string
     */
    public function getIntegrationType(): string
    {
        return GcpRequestInterface::GCP_TYPE_USER;
    }

    /**
     * @return string
     */
    public function getEntityName() : string
    {
        return CustomerDefinition::ENTITY_NAME;
    }


}
