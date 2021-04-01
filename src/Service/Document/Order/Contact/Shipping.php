<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order\Contact;

use Boxalino\DataIntegration\Service\Document\Order\Contact;

/**
 * Class Shipping
 * Access the shipping contact information
 *
 * @package Boxalino\DataIntegration\Service\Document\Order\Contact
 */
class Shipping extends Contact
{
    /**
     * @return false|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getStateId() : string
    {
        return $this->connection->fetchColumn("SELECT id FROM state_machine WHERE technical_name='order_delivery.state'");
    }

    /**
     * @return string
     */
    public function getSourceTable() : string
    {
        return "order_delivery";
    }

    /**
     * @return string
     */
    public function getAddressCondition(): string
    {
        return "oa.version_id=src.shipping_order_address_version_id AND oa.order_id = src.order_id AND oa.order_version_id = src.order_version_id AND oa.id=src.shipping_order_address_id";
    }

    /**
     * @return string
     */
    public function getAddressJoinSrc(): string
    {
        return "src";
    }

    /**
     * @return string
     */
    public function getContactType(): string
    {
        return "shipping";
    }

    /**
     * @return string
     */
    public function getTypeStatusSchemaField(): string
    {
        return "status";
    }

}
