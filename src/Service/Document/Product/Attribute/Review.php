<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Typed\NumericAttribute;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class ReviewCount
 *
 * Counts all the reviews per SW6 logic (as seen on route /store-api/v{version}/product/{productId}/reviews )
 * : active reviews
 * : reviews for product.id or product.parent_id
 *
 * Adds a localized property "di_review_count" (@todo)
 * Adds a numeric property "di_review_total"
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Review extends ModeIntegrator
{

    use DeltaInstantTrait;

    PUBLIC CONST BOXALINO_DATA_INTEGRATION_FIELD_REVIEW_TOTAL = "di_review_total";

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getSystemConfiguration()->getLanguages();

        foreach ($this->getData() as $item)
        {
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_NUMERIC] = [];
            }

            if(is_null($item[DocSchemaInterface::FIELD_INTERNAL_ID]))
            {
                continue;
            }

            /** @var NumericAttribute $schema */
            $total = $this->getNumericAttributeSchema([$item[DocSchemaInterface::FIELD_INTERNAL_ID]], self::BOXALINO_DATA_INTEGRATION_FIELD_REVIEW_TOTAL, null);
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_NUMERIC][] = $total->toArray();
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
            ->from("product_review", "pr")
            ->leftJoin("pr", "( " . $this->_getProductQuery(["product.id", "product.parent_id"])->__toString() . " )", "product",
                "product.id = pr.product_id OR product.parent_id = pr.product_id"
            )
            ->andWhere("product.id IS NOT NULL OR product.parent_id IS NOT NULL")
            ->andWhere("pr.status = 1")
            ->addGroupBy("product.id")
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING);

        return $query;
    }

    /**
     * @return string[]
     */
    protected function _getQueryFields() : array
    {
        return [
            "LOWER(HEX(product.id)) AS {$this->getDiIdField()}",
            'COUNT(pr.points) AS ' . DocSchemaInterface::FIELD_INTERNAL_ID
        ];
    }


}
