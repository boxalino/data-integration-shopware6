<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DeltaCliCommand
 *
 * Use to trigger the data integration processes
 * ex: php bin/console boxalino:data-integration:full  [account]
 *
 * @package Boxalino\DataIntegration\Service
 */
class DeltaCliCommand extends Command
{
    protected static $defaultName = 'boxalino:data-integration:delta';


    public function __construct(
    ){
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription("Boxalino Delta Data Integration Command. Accepts parameters [type] [account]")
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
        $output->writeln('Start of Delta Boxalino Data Integration (DI) for '. $type .'...');

        //get service for the type
        //set account (if set)
        //export

        try{
            if(!empty($account))
            {
                $this->exporterFull->setAccount($account);
            }
            $this->exporterFull->export();
        } catch (\Exception $exc)
        {
            $output->writeln($exc->getMessage());
        }

        $output->writeln("End of Boxalino Delta Data Integration Process.");
        return 0;
    }

}
