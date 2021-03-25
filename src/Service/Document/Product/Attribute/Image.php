<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegration\Service\Util\ShopwareMediaTrait;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Repeated;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\DataAbstractionLayer\MediaRepositoryDecorator;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Image
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Image extends IntegrationSchemaPropertyHandler
{
    use ShopwareMediaTrait;

    /**
     * Media constructor.
     * @param Connection $connection
     * @param LoggerInterface $boxalinoLogger
     * @param UrlGeneratorInterface $generator
     * @param MediaRepositoryDecorator $mediaRepository
     */
    public function __construct(
        Connection $connection,
        LoggerInterface $boxalinoLogger,
        UrlGeneratorInterface $generator,
        EntityRepositoryInterface $mediaRepository
    ){
        $this->logger=$boxalinoLogger;
        $this->mediaRepository = $mediaRepository;
        $this->mediaUrlGenerator = $generator;
        $this->context = Context::createDefaultContext();
        parent::__construct($connection);
    }

    /**
     * @return array
     */
    public function getValues() : array
    {
        $content = [];
        $languages = $this->getConfiguration()->getLanguages();
        foreach ($this->getData() as $item)
        {
            if($item[DocSchemaInterface::FIELD_INTERNAL_ID])
            {
                $item = array_merge($item,
                    array_fill_keys($languages, $this->getImageByMediaId($item[DocSchemaInterface::FIELD_INTERNAL_ID]))
                );

                /** @var Repeated $schema */
                $schema = $this->getRepeatedLocalizedSchema($item, $languages);
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_IMAGES] = [$schema];
            }
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
        $query->select(
            ["LOWER(HEX(product.id)) AS {$this->getDiIdField()}",
            "LOWER(HEX(product_media.media_id)) AS " . DocSchemaInterface::FIELD_INTERNAL_ID]
        )
            ->from("product")
            ->leftJoin('product','product_media', 'product_media',
                'product.product_media_id = product_media.id AND product_media.version_id=:live'
            )
            ->andWhere('product.version_id = :live')
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL")
            ->addGroupBy('product.id')
            ->setParameter('channelRootCategoryId', $this->getConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);
        #->setFirstResult(($page - 1) * \Boxalino\Exporter\Service\Component\ProductComponentInterface::EXPORTER_STEP)
        #->setMaxResults(ProductComponentInterface::EXPORTER_STEP);

        /**
        $productIds = $this->getExportedProductIds();
        if(!empty($productIds))
        {
        $query->andWhere('product.id IN (:ids)')
        ->setParameter('ids', Uuid::fromHexToBytesList($productIds), Connection::PARAM_STR_ARRAY);
        }
         */

        return $query;
    }


}
