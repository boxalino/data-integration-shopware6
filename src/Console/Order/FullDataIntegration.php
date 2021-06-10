<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Order;

use Boxalino\DataIntegration\Integrate\Mode\Configuration\FullTrait;
use Boxalino\DataIntegration\Integrate\Type\OrderTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Console\DiGenericAbstractCommand;

/**
 * Class FullDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:full:order [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class FullDataIntegration extends DiGenericAbstractCommand
{
    use FullTrait;
    use OrderTrait;

    protected static $defaultName = 'boxalino:di:full:order';

    /**
     * @var OrderIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        OrderIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager);
    }

    public function getDescription(): string
    {
        return "Boxalino Full Order Data Integration. Accepts parameters [account]";
    }


}
