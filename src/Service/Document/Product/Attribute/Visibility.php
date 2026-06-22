<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Boxalino\DataIntegrationDoc\Doc\Schema\Visibility as VisibilitySchema;

/**
 * Class Visibility
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Visibility extends ModeIntegrator
{
    use DeltaInstantTrait;

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getSystemConfiguration()->getLanguages();
        $iterator = $this->getQueryIterator($this->getStatementQuery());

        foreach ($iterator->getIterator() as $item)
        {
            /** @var VisibilitySchema $schema */
            $schema = $this->getVisibilitySchema($languages, $item[DocSchemaInterface::FIELD_INTERNAL_ID]);
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_VISIBILITY][] = $schema->toArray();
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function _getQuery(?string $propertyName = null): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->_getQueryFields())
            ->from("( " . $this->_getProductQuery()->__toString() . " )", "product")
            ->leftJoin("product", 'product_visibility', 'product_visibility',
                'product.id = product_visibility.product_id AND product.version_id = product_visibility.product_version_id')
            ->andWhere('product_visibility.product_version_id = :live')
            ->andWhere('product_visibility.sales_channel_id = :channelId')
            ->addGroupBy('product.id')
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    /**
     * @return string[]
     */
    protected function _getQueryFields() : array
    {
        return ["LOWER(HEX(product.id)) AS {$this->getDiIdField()}", "product_visibility.visibility AS " . DocSchemaInterface::FIELD_INTERNAL_ID];
    }


}
