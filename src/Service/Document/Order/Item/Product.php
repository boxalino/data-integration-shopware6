<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order\Item;

use Boxalino\DataIntegration\Service\Document\Order\Item;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;
use Boxalino\DataIntegrationDoc\Doc\Schema\Order\Product as OrderProductSchema;

/**
 * Class Product
 * Access the order product information, following the documented schema
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
class Product extends Item
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
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_PRODUCTS] = [];
            }

            $schema = new OrderProductSchema($item);
            if(isset($item["options"]))
            {
                $options = json_decode($item["options"], true);
                foreach($options as $option)
                {
                    $stringAttribute = $this->getStringAttributeSchema([$option['option']], $option['group']);
                    $schema->addStringAttribute($stringAttribute);
                }
            }

            if(isset($item["label"]))
            {
                $stringAttribute = $this->getStringAttributeSchema([$item["label"]], "label");
                $schema->addStringAttribute($stringAttribute);
            }

            if(isset($item["purchase_price"]))
            {
                $stringAttribute = $this->getNumericAttributeSchema([$item["purchase_price"]], "purchase_price");
                $schema->addNumericAttribute($stringAttribute);
            }

            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_PRODUCTS][] = $schema->toArray();
        }

        return $content;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return "product";
    }


    /**
     * @return string[]
     */
    public function getFields() : array
    {
        return [
            "LOWER(HEX(oli.order_id)) AS ". $this->getDiIdField(),
            "oli.identifier AS sku_id",
            "'id' AS connection_property",
            "oli.type AS type",
            "oli.label AS label",
            "oli.quantity AS quantity",
            "JSON_EXTRACT(oli.payload, '$.options') AS options", //use options of the product as localized string
            "TRUNCATE(oli.unit_price,2) AS unit_sales_price",
            "TRUNCATE(oli.total_price,2) AS total_sales_price",
            "IF(JSON_EXTRACT(oli.price, '$.listPrice.price') IS NULL, TRUNCATE(oli.unit_price,2), JSON_EXTRACT(oli.price, '$.listPrice.price')) AS unit_list_price",
            "IF(JSON_EXTRACT(oli.price, '$.listPrice.price') IS NULL, TRUNCATE(oli.total_price,2), TRUNCATE(JSON_EXTRACT(oli.price, '$.listPrice.price')*oli.quantity, 2)) AS total_list_price",
            "TRUNCATE(oli.unit_price - JSON_EXTRACT(oli.payload, '$.purchasePrice'),2) AS unit_gross_margin",  //get unit gross margin from unit_price-purchasePrice
            "TRUNCATE(JSON_EXTRACT(oli.payload, '$.purchasePrice')*oli.quantity,2) AS total_gross_margin", //calculate total gross margin from quantity*unit_gross_margin
            "TRUNCATE(JSON_EXTRACT(oli.payload, '$.purchasePrice'),2) AS purchase_price",
        ];
    }


}
