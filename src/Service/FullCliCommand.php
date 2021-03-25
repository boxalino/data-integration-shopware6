<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service;

use Boxalino\DataIntegration\Service\Util\Configuration;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailDocLoadException;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailSyncException;
use Boxalino\DataIntegrationDoc\Service\GcpClientInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class FullCliCommand
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:data-integration:full [type] [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class FullCliCommand extends Command
{
    protected static $defaultName = 'boxalino:data-integration:full';


    /**
     * @var GcpClientInterface
     */
    protected $client;

    /**
     * @var ProductIntegrationHandlerInterface
     */
    protected $integrationHandler;

    /**
     * @var Configuration
     */
    protected $configurationManager;

    public function __construct(
        Configuration $configurationManager,
        GcpClientInterface $client,
        ProductIntegrationHandlerInterface $integrationHandler
    ){
        $this->configurationManager = $configurationManager;
        $this->client = $client;
        $this->integrationHandler = $integrationHandler;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription("Boxalino Full Data Integration Command. Accepts parameters [type] [account]")
            ->setHelp("This command allows you to update the Boxalino SOLR data index with your current data.");

        $this->addArgument(
            "type", InputArgument::REQUIRED, "Document Type: product, user, order, etc"
        );

        $this->addArgument(
            "account", InputArgument::OPTIONAL, "Boxalino Account"
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument("type");
        $account = $input->getArgument("account");
        $output->writeln('Start of Full Boxalino Data Integration (DI) for '. $type .'...');

        //get service for the type
        //set account (if set)
        //export

        try{
            if(!empty($account))
            {
                /** @var ConfigurationDataObject $configuration */
                foreach($this->configurationManager->getFullConfigurations() as $configuration)
                {
                    try {
                        $configuration->setData("type", $this->integrationHandler->getIntegrationType());
                        $this->integrationHandler->setIds([])->setConfiguration($configuration);
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

        $output->writeln("End of Boxalino Full Data Integration Process.");
        return 0;
    }

}
