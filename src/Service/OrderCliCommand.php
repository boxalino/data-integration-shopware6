<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service;

use Boxalino\DataIntegration\Service\Util\Configuration;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailDocLoadException;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailSyncException;
use Boxalino\DataIntegrationDoc\Service\GcpClientInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OrderCliCommand
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:data-integration:order [mode] [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class OrderCliCommand extends Command
{
    protected static $defaultName = 'boxalino:data-integration:order';

    /**
     * @var GcpClientInterface
     */
    protected $client;

    /**
     * @var OrderIntegrationHandlerInterface
     */
    protected $integrationHandler;

    /**
     * @var Configuration
     */
    protected $configurationManager;

    public function __construct(
        Configuration $configurationManager,
        GcpClientInterface $client,
        OrderIntegrationHandlerInterface $integrationHandler
    ){
        $this->configurationManager = $configurationManager;
        $this->client = $client;
        $this->integrationHandler = $integrationHandler;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription("Boxalino Order Data Integration Command. Accepts parameters [mode] [account]")
            ->setHelp("This command allows you to update the orders content directly in BigQuery.");

        $this->addArgument(
            "mode", InputArgument::REQUIRED, "Document Sync Mode: full, delta, instant"
        );

        $this->addArgument(
            "account", InputArgument::OPTIONAL, "Boxalino Account"
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument("mode");
        $account = $input->getArgument("account");
        $output->writeln('Start of Boxalino Order Data Integration (DI) for '. $type .'...');

        try{
            if(!empty($account))
            {
                /** @var ConfigurationDataObject $configuration */
                foreach($this->configurationManager->getFullConfigurations() as $configuration)
                {
                    try {
                        $configuration->setData("type", $this->integrationHandler->getIntegrationType());
                        $this->integrationHandler->setConfiguration($configuration);
                        $documents = $this->integrationHandler->getDocs();
                        $this->client->send($configuration, $documents, $this->integrationHandler->getIntegrationStrategy());
                    } catch (FailDocLoadException $exception)
                    {
                        //maybe a fallback to save the content of the documents and try again later or have the integration team review
                        $this->client->logOrThrowException($exception);
                    } catch (FailSyncException $exception)
                    {
                        //save that the product id was not synced (relevant for full error data sync alerts)
                        $this->client->logOrThrowException($exception);
                    } catch (\Throwable $exception)
                    {
                        $this->client->logOrThrowException($exception);
                    }
                }
            }
        } catch (\Exception $exc)
        {
            $output->writeln($exc->getMessage());
        }

        $output->writeln("End of Boxalino Order Data Integration Process.");
        return 0;
    }

}
