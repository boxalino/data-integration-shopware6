<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Order;

use Boxalino\DataIntegration\Console\Mode\DeltaTrait;
use Boxalino\DataIntegration\Console\Type\OrderTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderDeltaIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Console\GenericDiCommand;

/**
 * Class DeltaDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:delta:order [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class DeltaDataIntegration extends GenericDiCommand
{
    use DeltaTrait;
    use OrderTrait;

    protected static $defaultName = 'boxalino:di:delta:order';

    /**
     * @var OrderDeltaIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        OrderDeltaIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager);
    }

    public function getDescription(): string
    {
        return "Boxalino Delta Order Data Integration. Accepts parameters [account]";
    }

}
