<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Order\Product as OrderProductSchema;

/**
 * Class Product
 * Access the order product information, following the documented schema
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
class Product extends IntegrationSchemaPropertyHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        foreach ($this->getData() as $item)
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
                    $schema->addStringAttributes($stringAttribute);
                }
            }

            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_PRODUCTS][] = $schema;
        }

        return $content;
    }

    /**
     * The order Product schema properties are set in order to dynamically create the object
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields())
            ->from("order_line_item", "oli")
            ->leftJoin(
                "oli", '`order`', 'o', "oli.order_id = o.id AND oli.order_version_id = o.version_id"
            )
            //->andWhere("o.sales_channel_id=:channelId")
            ->andWhere("o.version_id = :live")
            //->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);
//                ->setFirstResult(($page - 1) * OrderComponentInterface::EXPORTER_STEP)
//                ->setMaxResults(OrderComponentInterface::EXPORTER_STEP);

        return $query;
    }

    /**
     * @return string[]
     */
    protected function getFields() : array
    {
        return [
            "LOWER(HEX(oli.order_id)) AS ". $this->getDiIdField(),
            "oli.identifier AS sku_id",
            "'id' AS connection_property",
            "oli.type AS type",
            "oli.quantity AS quantity",
            "JSON_EXTRACT(oli.payload, '$.options') AS options", //use options of the product as localized string
            "TRUNCATE(oli.unit_price,2) AS unit_sales_price",
            "TRUNCATE(oli.total_price,2) AS total_sales_price",
            "IF(JSON_EXTRACT(oli.price, '$.listPrice.price') IS NULL, TRUNCATE(oli.unit_price,2), JSON_EXTRACT(oli.price, '$.listPrice.price')) AS unit_list_price",
            "IF(JSON_EXTRACT(oli.price, '$.listPrice.price') IS NULL, TRUNCATE(oli.total_price,2), TRUNCATE(JSON_EXTRACT(oli.price, '$.listPrice.price')*oli.quantity, 2)) AS total_list_price",
            "TRUNCATE(oli.unit_price - JSON_EXTRACT(oli.payload, '$.purchasePrice'),2) AS unit_gross_margin",  //get unit gross margin from unit_price-purchasePrice
            "TRUNCATE(JSON_EXTRACT(oli.payload, '$.purchasePrice')*oli.quantity,2) AS total_gross_margin" //calculate total gross margin from quantity*unit_gross_margin
        ];
    }


}
