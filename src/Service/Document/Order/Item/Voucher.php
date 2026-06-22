<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order\Item;

use Boxalino\DataIntegration\Service\Document\Order\Item;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Order\Voucher as OrderVoucherSchema;

/**
 * Class Promotion
 * Access the order product information, following the documented schema
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
class Voucher extends Item
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $iterator = $this->getQueryIterator($this->getStatementQuery());

        foreach ($iterator->getIterator() as $item)
        {
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_VOUCHERS] = [];
            }

            $schema = new OrderVoucherSchema($item);
            $schema->addStringAttribute($this->getStringAttributeSchema([$item['payload']], 'payload'));

            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_VOUCHERS][] = $schema->toArray();
        }

        return $content;
    }

    /**
     * @return string
     */
    public function getTypeFilter(): string
    {
       return "oli.total_price < 0";
    }

    /**
     * @return string[]
     */
    public function getFields() : array
    {
        return [
            "LOWER(HEX(oli.order_id)) AS ". $this->getDiIdField(),
            "IFNULL(LOWER(HEX(oli.promotion_id)), LOWER(HEX(oli.identifier))) AS internal_id",
            "oli.referenced_id AS external_id",
            "oli.type AS type",
            "oli.label AS label",
            "oli.referenced_id AS ean",
            "TRUNCATE(oli.total_price, 2) AS voucher_absolute_value",
            "oli.payload AS payload"
        ];
    }


}
