<?php
namespace Boxalino\DataIntegration\Service\Util;

use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\GcpRequestInterface;
use Boxalino\DataIntegrationDoc\Service\Util\AbstractSimpleObject;
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
class Configuration implements DiConfigurationInterface
{

    use ShopwareConfigurationTrait;

    const BOXALINO_DI_INSTANT_CACHE_KEY = "boxalino_instant_update_cache";
    const BOXALINO_DI_FULL_CACHE_KEY = "boxalino_di_full_cache";
    const BOXALINO_DI_DELTA_CACHE_KEY = "boxalino_di_delta_cache";

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

    /**
     * @var array
     */
    protected $currencyFactorMap = null;

    public function __construct(
        SystemConfigService $systemConfigService,
        TagAwareAdapterInterface $cache,
        SalesChannelContextServiceInterface $salesChannelContextService,
        Connection $connection
    ) {
        $this->salesChannelContextService = $salesChannelContextService;
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
        $this->cache = $cache;
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

        $configurations = [];
        $this->loadChannelConfigurationList();
        foreach($this->configurations as $configuration)
        {
            $modeConfigurations = array_merge(
                $this->_getInstantConfigurations($configuration),
                $this->_getGenericConfigurations($configuration)
            );

            $configurations[] = new ConfigurationDataObject($modeConfigurations);
        }

        $item->set($configurations);
        $this->cache->save($item);

        return $configurations;
    }

    /**
     * @return array
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getFullConfigurations() : array
    {
        $item = $this->cache->getItem(self::BOXALINO_DI_FULL_CACHE_KEY);
        if ($item->isHit() && $item->get()) {
            return $item->get();
        }

        $configurations = [];
        $this->loadChannelConfigurationList();
        foreach($this->configurations as $configuration)
        {
            $modeConfigurations = array_merge(
                $this->_getFullConfigurations($configuration),
                $this->_getGenericConfigurations($configuration)
            );

            $configurations[] = new ConfigurationDataObject($modeConfigurations);
        }

        $item->set($configurations);
        $this->cache->save($item);

        return $configurations;
    }

    /**
     * @return array
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getDeltaConfigurations() : array
    {
        $item = $this->cache->getItem(self::BOXALINO_DI_DELTA_CACHE_KEY);
        if ($item->isHit() && $item->get()) {
            return $item->get();
        }

        $configurations = [];
        $this->loadChannelConfigurationList();
        foreach($this->configurations as $configuration)
        {
            $modeConfigurations = array_merge(
                $this->_getDeltaConfigurations($configuration),
                $this->_getGenericConfigurations($configuration)
            );

            $configurations[] = new ConfigurationDataObject($modeConfigurations);
        }

        $item->set($configurations);
        $this->cache->save($item);

        return $configurations;
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
     * @param array $configuration
     * @return array
     */
    protected function _getInstantConfigurations(array $configuration) : array
    {
        return [
            "mode" => GcpRequestInterface::GCP_MODE_INSTANT_UPDATE,
            "endpoint" => $configuration["instantDiEndpoint"],
            "allowProductSync" => isset($configuration['productInstantStatus']) ? (bool)$configuration['productInstantStatus'] : false,
            "allowUserSync" => isset($configuration['userInstantStatus']) ? (bool) $configuration['userInstantStatus'] : false,
            "allowOrderSync" => isset($configuration['orderInstantStatus']) ? (bool) $configuration['orderInstantStatus'] : false,
        ];
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function _getDeltaConfigurations(array $configuration) : array
    {
        return [
            "mode" => GcpRequestInterface::GCP_MODE_DELTA,
            "endpoint" => $configuration["deltaDiEndpoint"],
            "allowProductSync" => isset($configuration['productDeltaStatus']) ? (bool)$configuration['productDeltaStatus'] : false,
            "allowUserSync" => isset($configuration['userDeltaStatus']) ? (bool) $configuration['userDeltaStatus'] : false,
            "allowOrderSync" => isset($configuration['orderDeltaStatus']) ? (bool) $configuration['orderDeltaStatus'] : false,
        ];
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function _getFullConfigurations(array $configuration) : array
    {
        return [
            "mode" => GcpRequestInterface::GCP_MODE_FULL,
            "endpoint" => $configuration["fullDiEndpoint"],
            "allowProductSync" => isset($configuration['productDiStatus']) ? (bool)$configuration['productDiStatus'] : false,
            "allowUserSync" => isset($configuration['userDiStatus']) ? (bool) $configuration['userDiStatus'] : false,
            "allowOrderSync" => isset($configuration['orderDiStatus']) ? (bool) $configuration['orderDiStatus'] : false,
        ];
    }

    /**
     * @param array $configuration
     * @return array
     */
    protected function _getGenericConfigurations(array $configuration) : array
    {
        $salesChannelContext = $this->salesChannelContextService->get(
            $configuration['sales_channel_id'],
            "boxalinoinstantupdatetoken",
            $configuration['sales_channel_default_language_id']
        );

        $languagesCodeMap = array_combine(explode(",", $configuration['sales_channel_languages_locale']), explode(",", $configuration['sales_channel_languages_code']));
        $languagesMap = array_combine(explode(",", $configuration['sales_channel_languages_id']), explode(",", $configuration['sales_channel_languages_locale']));
        $currenciesMap = array_combine(explode(",", $configuration['sales_channel_currencies_id']), explode(",", $configuration['sales_channel_currencies_code']));
        return [
            "account" => $configuration['account'],
            "isDev" => (bool) $configuration['devIndex'],
            "isTest" => (bool) $configuration['isTest'],
            "apiKey" => $configuration["apiKey"],
            "apiSecret" => $configuration["apiSecret"],
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
            "currencyFactorMap" => $this->getCurrencyFactorMap(),
            "markAsNew" => $configuration["markAsNew"],
            "batchSize" => (int) $configuration['batchSize']
        ];
    }

    /**
     * Accessing the currency factor values
     *
     * @return array
     */
    protected function getCurrencyFactorMap() : array
    {
        if(is_null($this->currencyFactorMap))
        {
            $query = $this->connection->createQueryBuilder();
            $query->select("iso_code", "factor")
                ->from("currency");

            $map = $query->execute()->fetchAll();
            $this->currencyFactorMap =  array_combine(array_column($map, "iso_code"), array_column($map, "factor"));
        }

        return $this->currencyFactorMap;
    }


}
