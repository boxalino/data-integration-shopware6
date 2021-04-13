<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Mode;

/**
 * Class DeltaDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:delta:product [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
trait FullTrait
{

    /**
     * Access configurations for the full
     *
     * @return array
     * @throws \Psr\Cache\CacheException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getConfigurations(): array
    {
        return $this->getConfigurationManager()->getFullConfigurations();
    }


}
