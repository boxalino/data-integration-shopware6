<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Order;

use Boxalino\DataIntegration\Integrate\Mode\Configuration\DeltaTrait;
use Boxalino\DataIntegration\Integrate\Type\OrderTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderDeltaIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Console\DiGenericAbstractCommand;

/**
 * Class DeltaDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:delta:order [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class DeltaDataIntegration extends DiGenericAbstractCommand
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
