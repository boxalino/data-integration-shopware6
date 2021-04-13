<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;
use Boxalino\DataIntegration\Service\Util\ShopwareLocalizedTrait;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Link
 * Exporter for the seo_url property
 * The Shopware6 SEO property matches the "link" doc_product schema property
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Link extends ModeIntegrator
{

    use ShopwareLocalizedTrait;
    use DeltaInstantAddTrait;

    public function __construct(
        Connection $connection,
        StringLocalized $localizedStringBuilder
    ){
        $this->localizedStringBuilder = $localizedStringBuilder;
        parent::__construct($connection);
    }

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getSystemConfiguration()->getLanguages();
        foreach ($this->getData(DocSchemaInterface::FIELD_LINK) as $item)
        {
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_LINK] = [];
            }

            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_LINK] = $this->getLocalizedSchema($item, $languages);
        }

        return $content;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function _getQuery(?string $propertyName = null): QueryBuilder
    {
        $condition = "$this->prefix.foreign_key = product.id";
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields())
            ->from('product')
            ->leftJoin('product',
                '( ' . $this->getLocalizedFieldsQuery()->__toString() . ') ', $this->prefix, $condition)
            ->andWhere('product.version_id = :live')
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->addGroupBy('product.id')
            ->orderBy("product.created_at", "DESC")
            ->addOrderBy("product.auto_increment", "DESC")
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION))
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }


    /**
     * Prepare seo url joins
     * Filters are set as seen in Shopware\Core\Content\Seo\SeoResolver
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     * @throws \Exception
     */
    public function getLocalizedFieldsQuery() : QueryBuilder
    {
        return $this->localizedStringBuilder->getLocalizedFields('seo_url', 'id', 'id',
            'foreign_key','seo_path_info', ['seo_url.foreign_key', 'seo_url.sales_channel_id'],
            $this->getSystemConfiguration()->getLanguagesMap(), $this->getSystemConfiguration()->getDefaultLanguageId(),
            ["seo_url.route_name='frontend.detail.page'", "seo_url.is_canonical='1'", "LOWER(HEX(seo_url.sales_channel_id))='{$this->getSystemConfiguration()->getSalesChannelId()}' OR seo_url.sales_channel_id IS NULL"]
        );
    }


}
