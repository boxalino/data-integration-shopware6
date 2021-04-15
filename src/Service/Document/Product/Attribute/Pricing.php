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

    use DeltaInstantTrait;

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
            ->where('product.active=1')
            ->groupBy('product.parent_id')
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
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
        return $this->_getProductQuery($this->getPriceFields());
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
            'LOWER(HEX(product.id)) AS ' . $this->getDiIdField(),
            "IF(product.parent_id IS NULL, LOWER(HEX(product.id)), LOWER(HEX(product.parent_id))) AS parent_id",
            "product.updated_at", "product.created_at", "product.active"
        ];

        if ($this->getSystemConfiguration()->getSalesChannelTaxState() === CartPrice::TAX_STATE_GROSS) {
            return array_merge($baseFields, [
                'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(product.price, \'$.*.gross\'),\'$[0]\'), 2), ",", "") AS price',
            ]);
        }

        return array_merge($baseFields, [
            'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(product.price, \'$.*.net\'),\'$[0]\'), 2), ",", "") AS price',
        ]);
    }

}
