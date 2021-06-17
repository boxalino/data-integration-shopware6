<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegration\Service\Util\ShopwareMediaTrait;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Repeated;
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
 * Class Image
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Image extends ModeIntegrator
{
    use ShopwareMediaTrait;
    use DeltaInstantTrait;

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
        $languages = $this->getSystemConfiguration()->getLanguages();
        $iterator = $this->getQueryIterator($this->getStatementQuery(DocSchemaInterface::FIELD_IMAGES));
        $this->prepareMediaRepositoryCollection();

        foreach ($iterator->getIterator() as $item)
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
    public function _getQuery(?string $propertyName = null): QueryBuilder
    {
        return $this->_getProductQuery($this->_getQueryFields())
            ->leftJoin('product','product_media', 'product_media',
                'product.cover = product_media.id AND product_media.version_id=product.version_id'
            )
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING);
    }

    /**
     * @return string[]
     */
    public function _getQueryFields() : array
    {
        return [
            "LOWER(HEX(product.id)) AS {$this->getDiIdField()}",
            "LOWER(HEX(product_media.media_id)) AS " . DocSchemaInterface::FIELD_INTERNAL_ID
        ];
    }

    /**
     * Create a media collection once, instead of calling it every time
     */
    protected function prepareMediaRepositoryCollection() : void
    {
        $ids = $this->getStatementQuery()->fetchAll(FetchMode::COLUMN, 1);
        if(count($ids))
        {
            $this->mediaCollection = $this->mediaRepository->search(new Criteria($ids), $this->context);
        }
    }

}
