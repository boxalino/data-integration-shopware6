<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order\Contact;

use Boxalino\DataIntegration\Service\Document\Order\Contact;

/**
 * Class Billing
 * Access the billing information
 *
 * @package Boxalino\DataIntegration\Service\Document\Order\Contact
 */
class Billing extends Contact
{

    /**
     * @return false|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getStateId() : string
    {
        return $this->connection->fetchColumn("SELECT id FROM state_machine WHERE technical_name='order_transaction.state'");
    }

    /**
     * @return string
     */
    public function getSourceTable() : string
    {
        return "order_transaction";
    }

    /**
     * @return string
     */
    public function getAddressCondition(): string
    {
        return "oa.version_id=o.billing_address_version_id AND oa.order_id = o.id AND oa.order_version_id = o.version_id AND oa.id=o.billing_address_id";
    }

    /**
     * @return string
     */
    public function getAddressJoinSrc(): string
    {
        return "o";
    }

    /**
     * @return string
     */
    public function getContactType(): string
    {
        return "billing";
    }

    /**
     * @return string
     */
    public function getTypeStatusSchemaField(): string
    {
        return "invoice_status";
    }

}
