<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegration\Service\Util\ShopwareMediaTrait;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Typed\StringAttribute;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\DataAbstractionLayer\MediaRepositoryDecorator;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Media
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Media extends ModeIntegrator
{
    use ShopwareMediaTrait;
    use DeltaInstantAddTrait;

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
        $this->logger = $boxalinoLogger;
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
        $languages = $this->getSystemConfiguration()->getLanguages();
        $iterator = $this->getQueryIterator($this->getStatementQuery(DocSchemaInterface::FIELD_IMAGES));
        $this->prepareMediaRepositoryCollection();

        foreach ($iterator->getIterator() as $item)
        {
            if(!isset($content[$item[$this->getDiIdField()]]))
            {
                $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING] = [];
            }

            if(is_null($item[DocSchemaInterface::FIELD_INTERNAL_ID]))
            {
                continue;
            }

            $productMediaLinks = array_filter(array_map(function(string $mediaId) {
                return $this->getImageByMediaId($mediaId);
            }, explode("|", $item[DocSchemaInterface::FIELD_INTERNAL_ID])));

            /** @var StringAttribute $schema */
            $schema = $this->getStringAttributeSchema($productMediaLinks, "absolute_media_url");
            $content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_STRING][] = $schema;
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
            ->from("product_media")
            ->leftJoin("product_media", "( " . $this->_getProductQuery()->__toString() . " )", 'product',
                'product_media.product_id = product.id AND product_media.product_version_id = product.version_id')
            ->andWhere('product_media.version_id = :liveVersion')
            ->andWhere("product.id IS NOT NULL")
            ->addGroupBy("product.id")
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('productLiveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('liveVersion', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING);

        return $query;
    }

    /**
     * @return string[]
     */
    public function _getQueryFields() : array
    {
        return [
            "LOWER(HEX(product.id)) AS {$this->getDiIdField()}",
            "GROUP_CONCAT(LOWER(HEX(product_media.media_id)) ORDER BY product_media.position SEPARATOR '|') AS " . DocSchemaInterface::FIELD_INTERNAL_ID
        ];
    }

    /**
     * Create a media collection once, instead of calling it every time
     */
    protected function prepareMediaRepositoryCollection() : void
    {
        $query = $this->getStatementQuery();
        if($query)
        {
            $ids = $query->fetchAll(FetchMode::COLUMN, 1);
            if(count($ids))
            {
                /** @var array $mediaIdList fetches IDs */
                $mediaIdList = call_user_func_array("array_merge", array_filter(array_map(function($row) {
                    return array_merge(explode("|", $row));
                }, $ids)));

                $this->mediaCollection = $this->mediaRepository->search(new Criteria(array_filter($mediaIdList)), $this->context);
            }
        }
    }


}
