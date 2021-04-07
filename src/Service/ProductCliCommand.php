<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegration\Service\Util\Configuration;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailDocLoadException;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\FailSyncException;
use Boxalino\DataIntegrationDoc\Service\Integration\ProductIntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProductCliCommand
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:data-integration:product [mode] [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class ProductCliCommand extends Command
{
    protected static $defaultName = 'boxalino:data-integration:product';

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ProductIntegrationHandlerInterface
     */
    protected $integrationHandler;

    /**
     * @var Configuration
     */
    protected $configurationManager;

    /**
     * @var string
     */
    protected $environment;

    public function __construct(
        Configuration $configurationManager,
        ProductIntegrationHandlerInterface $integrationHandler,
        LoggerInterface $logger,
        string $environment
    ){
        $this->configurationManager = $configurationManager;
        $this->integrationHandler = $integrationHandler;
        $this->logger = $logger;
        $this->environment = $environment;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription("Boxalino Full Data Integration Command. Accepts parameters [type] [account]")
            ->setHelp("This command allows you to update the Boxalino SOLR data index with your current data.");

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
        $output->writeln('Start of Boxalino Product Data Integration (DI) for '. $type .'...');

        try{
            /** @var ConfigurationDataObject $configuration */
            foreach($this->configurationManager->getFullConfigurations() as $configuration)
            {
                try {
                    $this->integrationHandler->setSystemConfiguration($configuration);

                    $this->integrationHandler
                        ->addConfigurationScope($configuration)
                        ->integrate();
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
        } catch (\Exception $exc)
        {
            $output->writeln($exc->getMessage());
        }

        $output->writeln("End of Boxalino Product Data Integration Process.");
        return 0;
    }

    /**
     * Do not throw exception, the product update must not be blocked if the SOLR SYNC update does not work
     *
     * @param \Throwable $exception
     * @return bool
     * @throws \Throwable
     */
    public function logOrThrowException(\Throwable $exception)
    {
        if ($this->environment === 'prod') {
            $this->logger->warning("Boxalino API Data Integration error: " . $exception->getMessage());
            throw $exception;
        }

        $this->logger->info("Boxalino API Data Integration error: " . $exception->getMessage());
        throw $exception;
    }

}
