<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\PriceLocalized;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Price as PriceSchema;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
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
class Price extends IntegrationSchemaPropertyHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $currencyFactor = $this->getConfiguration()->getCurrencyFactorMap();
        $languages = $this->getConfiguration()->getLanguages();
        $currencyCodes = $this->getConfiguration()->getCurrencies();
        foreach ($this->getData() as $item)
        {
            if(is_null($item['list_price']) && is_null($item['price']))
            {
                continue;
            }

            $schema = $this->getPriceSchema($languages, $currencyCodes, $currencyFactor, $item['price'], $item['list_price']);
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_PRICE] = $schema;
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function getQuery(?string $propertyName = null): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getRequiredFields())
            ->from("product")
            ->andWhere('version_id = :live')
            ->andWhere("JSON_SEARCH(category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            #->andWhere('id IN (:ids)')
            #->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
            ->setParameter("channelRootCategoryId", $this->getConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    /**
     * Depending on the channel configuration, the gross or net price is the one displayed to the user
     * @duplicate logic from the src/Core/Content/Product/SalesChannel/Price/ProductPriceDefinitionBuilder.php :: getPriceForTaxState()
     *
     * @return array
     * @throws \Exception
     */
    public function getRequiredFields(): array
    {
        if ($this->getConfiguration()->getSalesChannelTaxState() === CartPrice::TAX_STATE_GROSS) {
            return [
                'LOWER(HEX(id)) AS ' . $this->getDiIdField(),
                'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(price, \'$.*.gross\'),\'$[0]\'), 2), ",", "") AS price',
                'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(price, \'$.*.listPrice\'),\'$[0].gross\'), 2), ",", "") AS list_price'
            ];
        }

        return [
            'LOWER(HEX(id)) AS ' . $this->getDiIdField(),
            'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(price, \'$.*.net\'),\'$[0]\'), 2), ",", "") AS price',
            'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(price, \'$.*.listPrice\'),\'$[0].net\'), 2), ",", "") AS list_price'
        ];
    }


}
