<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\User\Contact;

use Boxalino\DataIntegration\Service\Document\User\Contact;

/**
 * Class Shipping
 * Access the shipping contact information
 *
 * @package Boxalino\DataIntegration\Service\Document\User\Contact
 */
class Shipping extends Contact
{

    /**
     * @return string
     */
    public function getAddressJoinCondition(): string
    {
        return "customer_address.id=customer.default_shipping_address_id";
    }

    /**
     * @return string
     */
    public function getContactType(): string
    {
        return "shipping";
    }


}
