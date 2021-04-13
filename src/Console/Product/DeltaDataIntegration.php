<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Product;

use Boxalino\DataIntegration\Console\Mode\DeltaTrait;
use Boxalino\DataIntegration\Console\Type\ProductTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductDeltaIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Console\GenericDiCommand;

/**
 * Class DeltaDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:delta:product [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class DeltaDataIntegration extends GenericDiCommand
{
    use DeltaTrait;
    use ProductTrait;

    protected static $defaultName = 'boxalino:di:delta:product';

    /**
     * @var ProductDeltaIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        ProductDeltaIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager);
    }

    public function getDescription(): string
    {
        return "Boxalino Delta Product Data Integration. Accepts parameters [account]";
    }


}
