<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Price
 *
 * By default, just the default currency values are exported
 * For more options - please customize
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Price extends ModeIntegrator
{

    use DeltaInstantTrait;

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $currencyFactor = $this->getSystemConfiguration()->getCurrencyFactorMap();
        $languages = $this->getSystemConfiguration()->getLanguages();
        $currencyCodes = $this->getSystemConfiguration()->getCurrencies();
        $iterator = $this->getQueryIterator($this->getStatementQuery());

        foreach ($iterator->getIterator() as $item)
        {
            if(is_null($item['list_price']) && is_null($item['price']))
            {
                continue;
            }

            if($item['price']<$item['list_price'])
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_SALE] = true;
            }

            $schema = $this->getPriceSchema($languages, $currencyCodes, $currencyFactor, $item['price'], $item['list_price']);
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_PRICE] = $schema->toArray();
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function _getQuery(?string $propertyName = null): QueryBuilder
    {
        return $this->_getProductQuery($this->getFields())
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter("channelRootCategoryId", $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);
    }

    /**
     * Depending on the channel configuration, the gross or net price is the one displayed to the user
     * @duplicate logic from the src/Core/Content/Product/SalesChannel/Price/ProductPriceDefinitionBuilder.php :: getPriceForTaxState()
     *
     * @return array
     * @throws \Exception
     */
    public function getFields(): array
    {
        if ($this->getSystemConfiguration()->getSalesChannelTaxState() === CartPrice::TAX_STATE_GROSS) {
            return [
                'LOWER(HEX(product.id)) AS ' . $this->getDiIdField(),
                'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(product.price, \'$.*.gross\'),\'$[0]\'), 2), ",", "") AS price',
                'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(product.price, \'$.*.listPrice\'),\'$[0].gross\'), 2), ",", "") AS list_price'
            ];
        }

        return [
            'LOWER(HEX(product.id)) AS ' . $this->getDiIdField(),
            'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(product.price, \'$.*.net\'),\'$[0]\'), 2), ",", "") AS price',
            'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(product.price, \'$.*.listPrice\'),\'$[0].net\'), 2), ",", "") AS list_price'
        ];
    }


}
