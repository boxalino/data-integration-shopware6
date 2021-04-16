<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\User\Contact;

use Boxalino\DataIntegration\Service\Document\User\Contact;

/**
 * Class Billing
 * Access the billing information
 *
 * @package Boxalino\DataIntegration\Service\Document\User\Contact
 */
class Billing extends Contact
{

    /**
     * @return string
     */
    public function getAddressJoinCondition(): string
    {
        return "customer_address.id=customer.default_billing_address_id";
    }

    /**
     * @return string
     */
    public function getContactType(): string
    {
        return "billing";
    }


}
