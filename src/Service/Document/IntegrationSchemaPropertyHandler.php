<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document;

use Boxalino\DataIntegration\Service\Util\DiFlaggedIdsTrait;
use Boxalino\DataIntegrationDoc\Doc\DocOrderAttributeTrait;
use Boxalino\DataIntegrationDoc\Doc\DocProductAttributeTrait;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaPropertyHandlerInterface;
use Boxalino\DataIntegrationDoc\Doc\DocUserAttributeTrait;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\ModeDisabledException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Driver\StatementIterator;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class IntegrationPropertyHandler
 *
 * Handles the data integration logic for all doc content exports (order, customer, products)
 * (from the documented schema and/or available in the project)
 *
 * AS AN INTEGRATOR, YOU CAN DECIDE IF TO USE THE STATEMENT ITERATOR OR LOADING THE DATA VIA FETCHALL
 * BY DEFAULT - THE ITERATOR IS USED
 *
 * @package Boxalino\DataIntegration\Service\Document
 */
abstract class IntegrationSchemaPropertyHandler extends DocSchemaPropertyHandler
    implements \JsonSerializable, DocSchemaPropertyHandlerInterface, IntegrationDocHandlerInterface
{

    use IntegrationDocHandlerTrait;
    use DocProductAttributeTrait;
    use DocOrderAttributeTrait;
    use DocUserAttributeTrait;
    use DiFlaggedIdsTrait;

    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(
        Connection $connection
    ){
        $this->connection = $connection;
        parent::__construct();
    }

    /**
     * @return array
     */
    public function getData(?string $propertyName = null) : array
    {
        try {
            return $this->getQuery($propertyName)->execute()->fetchAll();
        } catch (ModeDisabledException $exception)
        {
            return [];
        } catch (\Throwable $exception)
        {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @return iterable | array
     * @throws \Exception
     */
    public function getQueryIterator(?Statement $statement = null) : iterable
    {
        try {
            if(is_null($statement))
            {
                return new \ArrayObject();
            }
            return new StatementIterator($statement);
        } catch (ModeDisabledException $exception)
        {
            return new \ArrayObject();
        } catch (\Throwable $exception)
        {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param string|null $propertyName
     * @return Statement | null
     * @throws \Exception
     */
    public function getStatementQuery(?string $propertyName = null) : ?Statement
    {
        try {
            return $this->getQuery($propertyName)->execute();
        } catch (ModeDisabledException $exception)
        {
            return null;
        } catch (\Throwable $exception)
        {
            throw new \Exception($exception->getMessage() . " \n " . $exception->getTraceAsString());
        }
    }

    /**
     * @param string $propertyName
     * @return QueryBuilder
     */
    abstract function getQuery(?string $propertyName = null) : QueryBuilder;

    /**
     * @return int
     */
    public function getFirstResultByBatch() : int
    {
        return (int)$this->getSystemConfiguration()->getBatchSize()*(int)$this->getSystemConfiguration()->getChunk();
    }


}
