<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Product;

use Boxalino\DataIntegration\Integrate\Mode\Configuration\InstantTrait;
use Boxalino\DataIntegration\Integrate\Type\ProductTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductInstantIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Console\DiGenericAbstractCommand;

/**
 * Class InstantDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:instant:product [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class InstantDataIntegration extends DiGenericAbstractCommand
{
    use InstantTrait;
    use ProductTrait;

    protected static $defaultName = 'boxalino:di:instant:product';

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        ProductInstantIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager);
    }

    public function getDescription(): string
    {
        return "Boxalino Instant Product Data Integration. Accepts parameters [account]";
    }


}
