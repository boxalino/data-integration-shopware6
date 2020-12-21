<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Component\ProductComponentInterface;
use Boxalino\DataIntegration\Service\ExporterConfigurationInterface;
use Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\AttributeHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\PricingLocalized;
use Boxalino\DataIntegrationDoc\Service\Integration\DocProduct\AttributeHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Pricing as PricingSchema;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Pricing
 *
 * By default, just the default currency values are exported
 * For more options - please customize
 *
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute
 */
class Pricing extends AttributeHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $currencyFactor = $this->getConfiguration()->getCurrencyFactorMap();
        foreach ($this->getData(AttributeHandlerInterface::ATTRIBUTE_TYPE_PRICING) as $item)
        {
            $schema = new PricingSchema();
            $label = ($item['min_price'] < $item['max_price']) ? "from" : "";
            foreach($this->getConfiguration()->getLanguages() as $language)
            {
                foreach($this->getConfiguration()->getCurrencies() as $currencyCode)
                {
                    $schema->addValue($this->getPrice($language, $currencyCode, $item['min_price'], $currencyFactor[$currencyCode], $label));
                }
            }

            $schema->setType("discounted");
            $content[$item[$this->getInstantUpdateIdField()]][AttributeHandlerInterface::ATTRIBUTE_TYPE_PRICING] = $schema;
        }

        return $content;
    }

    /**
     * Only take into account the value of active products (children)
     *
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function getQuery(string $propertyName): QueryBuilder
    {

        $query = $this->connection->createQueryBuilder();
        $query->select($this->getRequiredFields())
            ->from("(" .$this->getPriceQuery()->__toString().")", "product")
            ->groupBy('parent_id')
            ->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
            ->setParameter("channelRootCategoryId", $this->getConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getRequiredFields(): array
    {
        return [
            "parent_id AS {$this->getInstantUpdateIdField()}",
            'MIN(price) AS min_price',
            'MAX(price) AS max_price'
        ];
    }

    /**
     * @return QueryBuilder
     */
    protected function getPriceQuery() : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getPriceFields())
            ->from("product")
            ->andWhere('version_id = :live')
            ->andWhere("JSON_SEARCH(category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->andWhere("active = 1 OR parent_id IS NULL")
            ->andWhere('id IN (:ids)');

        return $query;
    }

    /**
     * Depending on the channel configuration, the gross or net price is the one displayed to the user
     * @duplicate logic from the src/Core/Content/Product/SalesChannel/Price/ProductPriceDefinitionBuilder.php :: getPriceForTaxState()
     *
     * @return array
     * @throws \Exception
     */
    public function getPriceFields(): array
    {
        $baseFields = [
            'LOWER(HEX(id)) AS ' . $this->getInstantUpdateIdField(),
            "IF(parent_id IS NULL, LOWER(HEX(id)), LOWER(HEX(parent_id))) AS parent_id"
        ];

        if ($this->getConfiguration()->getSalesChannelTaxState() === CartPrice::TAX_STATE_GROSS) {
            return array_merge($baseFields, [
                'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(price, \'$.*.gross\'),\'$[0]\'), 2), ",", "") AS price',
            ]);
        }

        return array_merge($baseFields, [
            'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(price, \'$.*.net\'),\'$[0]\'), 2), ",", "") AS price',
        ]);
    }

    /**
     * @param $language
     * @param $currencyCode
     * @param $value
     * @param $factor
     * @return PriceLocalized
     */
    protected function getPrice($language, $currencyCode, $value, $factor, $label) : PricingLocalized
    {
        $schema = new PricingLocalized();
        $schema->setValue(round($value*$factor, 2))
            ->setCurrency($currencyCode)
            ->setLanguage($language)
            ->setLabel($label);

        return $schema;
    }

}
