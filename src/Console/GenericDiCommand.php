<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegration\Service\Util\DiConfigurationInterface;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailDocLoadException;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailSyncException;
use Boxalino\DataIntegrationDoc\Service\GcpRequestInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenericDiCommand
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:di:full:order [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
abstract class GenericDiCommand extends Command
{

    use DiLoggerTrait;

    /**
     * @var DiConfigurationInterface
     */
    protected $configurationManager;

    public function __construct(
        string $environment,
        LoggerInterface $logger,
        DiConfigurationInterface $configurationManager
    ){
        $this->configurationManager = $configurationManager;
        $this->logger = $logger;
        $this->environment = $environment;

        parent::__construct();
    }

    /**
     * Logic for accessing the configurations
     * @return array
     */
    abstract function getConfigurations() : array;

    /**
     * @param ConfigurationDataObject $configurationDataObject
     * @return bool
     */
    abstract function canRun(ConfigurationDataObject $configurationDataObject) : bool;

    /**
     * @return IntegrationHandlerInterface
     */
    abstract function getIntegrationHandler() : IntegrationHandlerInterface;


    protected function configure()
    {
        $this->addArgument("account", InputArgument::OPTIONAL, "Boxalino Account");
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $account = $input->getArgument("account");
        $output->writeln('Start of Boxalino Data Integration (DI) ...');

        try{
            /** @var ConfigurationDataObject $configuration */
            foreach($this->getConfigurations() as $configuration)
            {
                if($this->canRun($configuration))
                {
                    if(empty($account))
                    {
                        $this->integrate($configuration);
                        continue;
                    }

                    if($configuration->getAccount() == $account)
                    {
                        $this->integrate($configuration);
                        break;
                    }
                }
            }
        } catch (\Exception $exc)
        {
            $output->writeln($exc->getMessage());
        }

        $output->writeln("End of Boxalino Data Integration Process.");
        return 0;
    }

    /**
     * @throws \Throwable
     */
    protected function integrate(ConfigurationDataObject $configuration) : void
    {
        try {
            $this->getLogger()->info(
                "Boxalino DI: Start {$this->getIntegrationHandler()->getIntegrationType()} {$this->getIntegrationHandler()->getIntegrationMode()} sync for {$configuration->getAccount()}"
            );

            $this->getIntegrationHandler()->setSystemConfiguration($configuration);
            $this->getIntegrationHandler()
                ->manageConfiguration($configuration)
                ->integrate();

            $this->getLogger()->info(
                "Boxalino DI: End of {$this->getIntegrationHandler()->getIntegrationType()} {$this->getIntegrationHandler()->getIntegrationMode()} sync for {$configuration->getAccount()}"
            );
        } catch (FailDocLoadException $exception)
        {
            //maybe a fallback to save the content of the documents and try again later or have the integration team review
            $this->logOrThrowException($exception);
        } catch (FailSyncException $exception)
        {
            //save that the product id was not synced (relevant for full error data sync alerts)
            $this->logOrThrowException($exception);
        } catch (\Throwable $exception)
        {
            $this->logOrThrowException($exception);
        }
    }

    /**
     * @return DiConfigurationInterface
     */
    public function getConfigurationManager() : DiConfigurationInterface
    {
        return $this->configurationManager;
    }

}
