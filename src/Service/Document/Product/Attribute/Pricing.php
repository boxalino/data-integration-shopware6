<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegrationDoc\Doc\Schema\PricingLocalized;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Pricing as PricingSchema;
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
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Pricing extends ModeIntegrator
{

    use DeltaInstantAddTrait;

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $currencyFactors = $this->getSystemConfiguration()->getCurrencyFactorMap();
        $languages = $this->getSystemConfiguration()->getLanguages();
        $currencyCodes = $this->getSystemConfiguration()->getCurrencies();
        foreach ($this->getData() as $item)
        {
            $label = ($item['min_price'] < $item['max_price']) ? "from" : "";

            /** @var PricingSchema $schema */
            $schema = $this->getPricingSchema($languages, $currencyCodes, $currencyFactors, $item['min_price'], $label);
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_PRICING] = $schema;
        }

        return $content;
    }

    /**
     * Only take into account the value of active products (children)
     *
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function _getQuery(?string $propertyName = null): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->_getQueryFields())
            ->from("(" .$this->getPriceQuery()->__toString().")", "product")
            ->where('active=1')
            ->groupBy('parent_id')
            ->setParameter("channelRootCategoryId", $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    /**
     * @return string[]
     */
    protected function _getQueryFields() : array
    {
        return [
            "parent_id AS {$this->getDiIdField()}",
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
            ->orderBy("product_number", "DESC")
            ->addOrderBy("created_at", "DESC")
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

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
            'LOWER(HEX(id)) AS ' . $this->getDiIdField(),
            "IF(parent_id IS NULL, LOWER(HEX(id)), LOWER(HEX(parent_id))) AS parent_id",
            "updated_at", "created_at", "active"
        ];

        if ($this->getSystemConfiguration()->getSalesChannelTaxState() === CartPrice::TAX_STATE_GROSS) {
            return array_merge($baseFields, [
                'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(price, \'$.*.gross\'),\'$[0]\'), 2), ",", "") AS price',
            ]);
        }

        return array_merge($baseFields, [
            'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(price, \'$.*.net\'),\'$[0]\'), 2), ",", "") AS price',
        ]);
    }

}
