<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Order;

use Boxalino\DataIntegration\Console\Mode\FullTrait;
use Boxalino\DataIntegration\Console\Type\OrderTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Console\GenericDiCommand;

/**
 * Class FullDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:full:order [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class FullDataIntegration extends GenericDiCommand
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
