<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\User;

use Boxalino\DataIntegration\Integrate\Mode\Configuration\FullTrait;
use Boxalino\DataIntegration\Integrate\Type\UserTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\UserIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Console\DiGenericAbstractCommand;

/**
 * Class FullDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:full:user [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class FullDataIntegration extends DiGenericAbstractCommand
{
    use FullTrait;
    use UserTrait;

    protected static $defaultName = 'boxalino:di:full:user';

    /**
     * @var UserIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        UserIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager);
    }

    public function getDescription(): string
    {
        return "Boxalino Full User Data Integration. Accepts parameters [account]";
    }


}
