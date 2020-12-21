<?php
namespace Boxalino\DataIntegration\Service\Util;

use Boxalino\DataIntegration\Service\DataIntegrationConfigurationInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Trait for accessing Shopware Configuration content
 *
 * @package Boxalino\DataIntegration\Service\Util
 */
trait ShopwareConfigurationTrait
{

    /**
     * @var SystemConfigService
     */
    protected $systemConfigService;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param string $id
     * @return array
     */
    public function getPluginConfigByChannelId($id) : array
    {
        if(empty($this->config) || !isset($this->config[$id]))
        {
            $allConfig = $this->systemConfigService->all($id);
            $this->config[$id] = $allConfig[DataIntegrationConfigurationInterface::BOXALINO_CONFIG_KEY]['config'];
        }

        return $this->config[$id];
    }

    /**
     * Getting shop details: id, languages, root category
     *
     * @return array
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    public function getChannelConfigurationList() : array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
            'LOWER(HEX(sales_channel.id)) as sales_channel_id',
            'LOWER(HEX(sales_channel.language_id)) AS sales_channel_default_language_id',
            'LOWER(HEX(sales_channel.currency_id)) AS sales_channel_default_currency_id',
            'LOWER(HEX(sales_channel.customer_group_id)) as sales_channel_customer_group_id',
            'MIN(channel.name) as sales_channel_name',
            "GROUP_CONCAT(SUBSTR(locale.code, 1, 2) SEPARATOR ',') as sales_channel_languages_locale",
            "GROUP_CONCAT(locale.code SEPARATOR ',') as sales_channel_languages_code",
            "GROUP_CONCAT(LOWER(HEX(language.id)) SEPARATOR ',') as sales_channel_languages_id",
            "GROUP_CONCAT(currency.iso_code SEPARATOR ',') as sales_channel_currencies_code",
            "GROUP_CONCAT(LOWER(HEX(sales_channel_currency.currency_id)) SEPARATOR ',') as sales_channel_currencies_id",
            'LOWER(HEX(sales_channel.navigation_category_id)) as sales_channel_navigation_category_id',
            'LOWER(HEX(sales_channel.navigation_category_version_id)) as sales_channel_navigation_category_version_id'
        ])
            ->from('sales_channel')
            ->leftJoin(
                'sales_channel',
                'sales_channel_language',
                'sales_channel_language',
                'sales_channel.id = sales_channel_language.sales_channel_id'
            )
            ->leftJoin(
                'sales_channel',
                'sales_channel_translation',
                'channel',
                'sales_channel.id = channel.sales_channel_id'
            )
            ->leftJoin(
                'sales_channel_language',
                'language',
                'language',
                'sales_channel_language.language_id = language.id'
            )
            ->leftJoin(
                'language',
                'locale',
                'locale',
                'language.locale_id = locale.id'
            )
            ->leftJoin(
                'sales_channel_currency',
                'currency',
                'currency',
                'sales_channel_currency.currency_id = currency.id'
            )
            ->leftJoin(
                'sales_channel',
                'sales_channel_currency',
                'sales_channel_currency',
                'sales_channel.id = sales_channel_currency.sales_channel_id'
            )
            ->groupBy('sales_channel.id')
            ->andWhere('sales_channel.active = 1')
            ->andWhere('sales_channel.type_id = :type')
            ->setParameter('type', Uuid::fromHexToBytes(Defaults::SALES_CHANNEL_TYPE_STOREFRONT), ParameterType::BINARY);

        return $query->execute()->fetchAll();
    }

}
