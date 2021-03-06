<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Product;

use Boxalino\DataIntegration\Integrate\Mode\Configuration\FullTrait;
use Boxalino\DataIntegration\Integrate\Type\ProductTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Console\DiGenericAbstractCommand;

/**
 * Class FullDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:full:product [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class FullDataIntegration extends DiGenericAbstractCommand
{
    use FullTrait;
    use ProductTrait;

    protected static $defaultName = 'boxalino:di:full:product';

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        ProductIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager);
    }

    public function getDescription(): string
    {
        return "Boxalino Full Product Data Integration. Accepts parameters [account]";
    }


}
