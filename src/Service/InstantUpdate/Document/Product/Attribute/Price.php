<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\AttributeHandler;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\PriceLocalized;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaPropertyHandlerInterface;
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
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute
 */
class Price extends AttributeHandler
{

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $currencyFactor = $this->getSystemConfiguration()->getCurrencyFactorMap();
        foreach ($this->getData(DocSchemaInterface::FIELD_PRICE) as $item)
        {
            $listPrices = []; $prices = [];
            foreach($this->getSystemConfiguration()->getLanguages() as $language)
            {
                foreach($this->getSystemConfiguration()->getCurrencies() as $currencyCode)
                {
                    if($item['price'])
                    {
                        $prices[] = $this->getPrice($language, $currencyCode, $item['price'], $currencyFactor[$currencyCode]);
                    }
                    if($item['list_price'])
                    {
                        $listPrices[] = $this->getPrice($language, $currencyCode, $item['list_price'], $currencyFactor[$currencyCode]);
                    }
                }
            }

            if(empty($prices) && empty($listPrices))
            {
                continue;
            }

            $schema = new PriceSchema();
            $schema->setSalesPrice($prices)
                ->setListPrice($listPrices);

            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_PRICE] = $schema;
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function getQuery(string $propertyName): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getRequiredFields())
            ->from("product")
            ->andWhere('version_id = :live')
            ->andWhere("JSON_SEARCH(category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->andWhere('id IN (:ids)')
            ->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
            ->setParameter("channelRootCategoryId", $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
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
    protected function getRequiredFields(): array
    {
        if ($this->getSystemConfiguration()->getSalesChannelTaxState() === CartPrice::TAX_STATE_GROSS) {
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

    /**
     * @param $language
     * @param $currencyCode
     * @param $value
     * @param $factor
     * @return PriceLocalized
     */
    protected function getPrice($language, $currencyCode, $value, $factor) : PriceLocalized
    {
        $schema = new PriceLocalized();
        $schema->setValue(round($value*$factor, 2))
            ->setCurrency($currencyCode)
            ->setLanguage($language);

        return $schema;
    }

}
