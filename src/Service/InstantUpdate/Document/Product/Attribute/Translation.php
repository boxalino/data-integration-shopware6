<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute;

use Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\AttributeHandler;
use Boxalino\DataIntegration\Service\Util\Document\StringLocalized;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\Localized;
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
 * @package Boxalino\DataIntegration\Service\InstantUpdate\Document\Product\Attribute
 */
class Translation extends AttributeHandler
{

    /**
     * @var StringLocalized
     */
    protected $localizedStringBuilder;

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
        foreach($this->getProperties() as $propertyName => $docAttributeName)
        {
            foreach ($this->getData($propertyName) as $item)
            {
                if(!isset($content[$item[$this->getInstantUpdateIdField()]]))
                {
                    $content[$item[$this->getInstantUpdateIdField()]][$docAttributeName] = [];
                }
                foreach($this->getConfiguration()->getLanguages() as $language)
                {
                    $localized = new Localized();
                    $localized->setLanguage($language)->setValue($item[$language]);
                    $content[$item[$this->getInstantUpdateIdField()]][$docAttributeName][] = $localized;
                }
            }
        }

        return $content;
    }

    /**
     * @param $propertyName
     * @return QueryBuilder
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    public function getQuery(?string $propertyName) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields())
            ->from("product")
            ->leftJoin('product', '( ' . $this->getLocalizedFieldsQuery($propertyName)->__toString() . ') ',
                'translation', 'translation.product_id = product.id AND product.version_id = translation.product_version_id')
            ->andWhere('product.version_id = :live')
            ->andWhere($this->getLanguageHeaderConditional())
            ->andWhere('product.id IN (:ids)')
            ->addGroupBy('product.id')
            ->setParameter('ids', Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY)
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
            $this->getConfiguration()->getLanguagesMap(), $this->getConfiguration()->getDefaultLanguageId()
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getFields() : array
    {
        return array_merge($this->getLanguageHeaderColumns(),["LOWER(HEX(product.id)) AS {$this->getInstantUpdateIdField()}"]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getLanguageHeaderConditional() : string
    {
        $conditional = [];
        foreach ($this->getLanguageHeaderColumns() as $column)
        {
            $conditional[]= "$column IS NOT NULL ";
        }

        return implode(" OR " , $conditional);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getLanguageHeaderColumns() : array
    {
        return preg_filter('/^/', 'translation.', $this->getConfiguration()->getLanguages());
    }

}
