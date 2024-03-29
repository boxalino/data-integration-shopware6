<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\Document\Product\ModeIntegrator;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;
use Boxalino\DataIntegration\Service\Util\ShopwareLocalizedTrait;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Translation
 * Exports attributes such as title, description, short_description, meta informations, etc
 * (content available in the Shopware6 "translation" table)
 *
 * @package Boxalino\DataIntegration\Service\Document\Product\Attribute
 */
class Translation extends ModeIntegrator
{
    use ShopwareLocalizedTrait;
    use DeltaInstantTrait;

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
        foreach($this->getProperties() as $propertyName => $docAttributeName)
        {
            /** on instant mode - export only the allowed properties */
            if(!$this->isPropertyAllowedOnInstantMode($propertyName) && $this->filterByIds())
            {
                continue;
            }

            $iterator = $this->getQueryIterator($this->getStatementQuery($propertyName));

            foreach ($iterator->getIterator() as $item)
            {
                if(!isset($content[$item[$this->getDiIdField()]]))
                {
                    $content[$item[$this->getDiIdField()]][$docAttributeName] = [];
                }

                $content[$item[$this->getDiIdField()]][$docAttributeName] = $this->getLocalizedSchema($item, $languages);
            }
        }

        return $content;
    }

    /**
     * @param $propertyName
     * @return QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    public function _getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields())
            ->from( "( " . $this->_getProductQuery()->__toString() . " )", "product")
            ->leftJoin('product', '( ' . $this->getLocalizedFieldsQuery($propertyName)->__toString() . ') ',
                $this->getPrefix(), "$this->prefix.product_id = product.id AND product.version_id = $this->prefix.product_version_id")
            ->andWhere('product.version_id = :live')
            ->andWhere($this->getLanguageHeaderConditional())
            ->addGroupBy('product.id')
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('channelRootCategoryId', $this->getSystemConfiguration()->getNavigationCategoryId(), ParameterType::STRING)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    public function getLocalizedFieldsQuery(string $propertyName) : QueryBuilder
    {
        return $this->localizedStringBuilder->getLocalizedFields('product_translation', 'product_id', 'product_id',
            'product_version_id', $propertyName, ['product_translation.product_id', 'product_translation.product_version_id'],
            $this->getSystemConfiguration()->getLanguagesMap(), $this->getSystemConfiguration()->getDefaultLanguageId()
        );
    }


}
