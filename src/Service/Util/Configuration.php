<?php
namespace Boxalino\DataIntegration\Service\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Boxalino\DataIntegration\Service\Util\ShopwareConfigurationTrait;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;


/**
 * Class Configuration
 * Accesses the configurations from cache
 *
 * @package Boxalino\DataIntegration\Service\Util
 */
class Configuration
{

    use ShopwareConfigurationTrait;

    const BOXALINO_DI_INSTANT_CACHE_KEY = "boxalino_instant_update_cache";
    const BOXALINO_DI_FULL_CACHE_KEY = "boxalino_di_full_cache";

    /**
     * @var TagAwareAdapterInterface
     */
    protected $cache;

    /**
     * @var array
     */
    protected $configurations = [];

    /**
     * @var SalesChannelContextServiceInterface
     */
    protected $salesChannelContextService;

    public function __construct(
        SystemConfigService $systemConfigService,
        TagAwareAdapterInterface $cache,
        SalesChannelContextServiceInterface $salesChannelContextService,
        Connection $connection
    ) {
        $this->salesChannelContextService = $salesChannelContextService;
        $this->systemConfigService = $systemConfigService;
        $this->cache = $cache;
        $this->connection = $connection;
    }

    /**
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     */
    public function loadChannelConfigurationList()
    {
        foreach($this->getChannelConfigurationList() as $shopData)
        {
            $pluginConfig = $this->getPluginConfigByChannelId($shopData['sales_channel_id']);
            if(!$pluginConfig['export'] || empty($pluginConfig['account']))
            {
                continue;
            }

            if(!isset($this->configurations[$pluginConfig['account']]))
            {
                $this->configurations[$pluginConfig['account']] = array_merge($shopData, $pluginConfig);
            }
        }
    }

    /**
     * @return array
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getInstantUpdateConfigurations() : array
    {
        $item = $this->cache->getItem(self::BOXALINO_DI_INSTANT_CACHE_KEY);
        if ($item->isHit() && $item->get()) {
            return $item->get();
        }

        $instantUpdateConfigurations = [];
        $this->loadChannelConfigurationList();
        $currencyFactorMap = $this->getCurrencyFactorMap();
        foreach($this->configurations as $configuration)
        {
            $salesChannelContext = $this->salesChannelContextService->get(
                $configuration['sales_channel_id'],
                "boxalinoinstantupdatetoken",
                $configuration['sales_channel_default_language_id']
            );

            $languagesCodeMap = array_combine(explode(",", $configuration['sales_channel_languages_locale']), explode(",", $configuration['sales_channel_languages_code']));
            $languagesMap = array_combine(explode(",", $configuration['sales_channel_languages_id']), explode(",", $configuration['sales_channel_languages_locale']));
            $currenciesMap = array_combine(explode(",", $configuration['sales_channel_currencies_id']), explode(",", $configuration['sales_channel_currencies_code']));
            $instantUpdateConfigurations[] = new ConfigurationDataObject([
                "allowInstantUpdateRequests" => (bool) $configuration['instantUpdateStatus'],
                "account" => $configuration['account'],
                "isDev" => (bool) $configuration['devIndex'],
                "isTest" => (bool) $configuration['isTest'],
                "apiKey" => $configuration["instantUpdateAccessKey"],
                "apiSecret" => $configuration["instantUpdateAccessSecret"],
                "endpoint" => $configuration["instantUpdateEndpoint"],
                "salesChannelId" => $configuration['sales_channel_id'],
                "salesChannelTaxState" => $salesChannelContext->getTaxState(),
                "defaultLanguageId" => $configuration['sales_channel_default_language_id'],
                "defaultCurrencyId" => $configuration["sales_channel_default_currency_id"],
                "defaultCurrencyCode" => $currenciesMap[$configuration["sales_channel_default_currency_id"]],
                "defaultLanguageCode" => $languagesMap[$configuration['sales_channel_default_language_id']],
                "customerGroupId" => $configuration["sales_channel_customer_group_id"],
                "navigationCategoryId" => $configuration["sales_channel_navigation_category_id"],
                "languages" => array_unique(explode(",", $configuration['sales_channel_languages_locale'])),
                "languagesMap" => $languagesMap,
                "languagesCountryCodeMap" => $languagesCodeMap,
                "currencies" => array_unique(explode(",", $configuration['sales_channel_currencies_code'])),
                "currenciesMap" => $currenciesMap,
                "currencyFactorMap" => $currencyFactorMap
            ]);
        }

        $item->set($instantUpdateConfigurations);
        $this->cache->save($item);

        return $instantUpdateConfigurations;
    }

    /**
     * Accessing the currency factor values
     *
     * @return array
     */
    protected function getCurrencyFactorMap() : array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select("iso_code", "factor")
            ->from("currency");

        $map = $query->execute()->fetchAll();
        return array_combine(array_column($map, "iso_code"), array_column($map, "factor"));
    }

}
