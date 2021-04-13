<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Product;

use Boxalino\DataIntegration\Console\Mode\FullTrait;
use Boxalino\DataIntegration\Console\Type\ProductTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Console\GenericDiCommand;

/**
 * Class FullDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:full:product [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class FullDataIntegration extends GenericDiCommand
{
    use FullTrait;
    use ProductTrait;

    protected static $defaultName = 'boxalino:di:full:product';

    /**
     * @var ProductIntegrationHandlerInterface
     */
    protected $integrationHandler;

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
