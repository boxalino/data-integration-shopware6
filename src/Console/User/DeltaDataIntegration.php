<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\User;

use Boxalino\DataIntegration\Integrate\Mode\DeltaTrait;
use Boxalino\DataIntegration\Integrate\Type\UserTrait;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\UserDeltaIntegrationHandlerInterface;
use Psr\Log\LoggerInterface;
use Boxalino\DataIntegration\Console\DiGenericAbstractCommand;

/**
 * Class DeltaDataIntegration
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:delta:user [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class DeltaDataIntegration extends DiGenericAbstractCommand
{
    use DeltaTrait;
    use UserTrait;

    protected static $defaultName = 'boxalino:di:delta:user';

    /**
     * @var UserDeltaIntegrationHandlerInterface
     */
    protected $integrationHandler;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager,
        UserDeltaIntegrationHandlerInterface $integrationHandler
    ){
        $this->integrationHandler = $integrationHandler;

        parent::__construct($environment, $logger, $configurationManager);
    }

    public function getDescription(): string
    {
        return "Boxalino Delta User Data Integration. Accepts parameters [account]";
    }


}
