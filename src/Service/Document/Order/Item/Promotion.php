<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order\Item;

use Boxalino\DataIntegration\Service\Document\Order\Item;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;
use Boxalino\DataIntegrationDoc\Doc\Schema\Order\Voucher as OrderVoucherSchema;

/**
 * Class Promotion
 * Access the order product information, following the documented schema
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
class Promotion extends Item
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

            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_VOUCHERS][] = new OrderVoucherSchema($item);
        }

        return $content;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
       return "promotion";
    }

    /**
     * @return string[]
     */
    public function getFields() : array
    {
        return [
            "LOWER(HEX(oli.order_id)) AS ". $this->getDiIdField(),
            "REPLACE(JSON_EXTRACT(oli.payload, '$.promotionId'), '\"','') AS internal_id", #promotion ID information
            "oli.referenced_id AS external_id",
            "REPLACE(JSON_EXTRACT(oli.price_definition, '$.type'), '\"','') AS type",
            "oli.label AS label",
            "oli.referenced_id AS ean",
            "IF(JSON_EXTRACT(oli.price_definition, '$.type')='percentage', REPLACE(JSON_EXTRACT(oli.payload, '$.value'), '\"',''),  NULL) AS voucher_percentage_value",
            "TRUNCATE(oli.total_price,2) AS voucher_absolute_value"
        ];
    }


}
