<?php declare(strict_types=1);

namespace Convo\Data\Mypdo;

class MypdoServiceParams extends \Convo\Core\Params\AbstractServiceParams
{

    /**
     * @var MypdoConnectionProvider
     */
    private $_conn;

    public function __construct( \Psr\Log\LoggerInterface $logger, \Convo\Core\Params\IServiceParamsScope $scope, MypdoConnectionProvider $conn)
    {
        parent::__construct( $logger, $scope);
    	$this->_conn		=	$conn;
    }

    public function getData()
    {
        $statement = $this->_conn->getConnection()->prepare('SELECT `value` FROM service_params WHERE
            service_id = :service_id AND scope_type = :scope_type AND level_type = :level_type AND `key` = :key');
        
        $this->_logger->debug( 'Fetching params for ['.$this->_scope.'] ...');
        
        $statement->execute([
            ':service_id' => $this->_scope->getServiceId(),
            ':scope_type' => $this->_scope->getScopeType(),
            ':level_type' => $this->_scope->getLevelType(),
            ':key'        => $this->_scope->getKey(),
        ]);
        
        $row = $statement->fetch( \PDO::FETCH_ASSOC);
        
        if ( !$row) {
            $this->_logger->debug( 'Returning empty ...');
            return [];
        }
        $this->_logger->debug( 'Returning data ['.$row['value'].'] ...');
        return json_decode( $row['value'], true);
    }

    protected function _storeData( $data)
    {
        $this->_logger->debug( 'Storing data ['.json_encode( $data, JSON_PRETTY_PRINT).'] for ['.$this->_scope.'] ...');
        $timeCreated = $this->_getTimeCreatedOfExistingServiceParam();

        $statement = $this->_conn->getConnection()->prepare('REPLACE INTO service_params ( service_id, scope_type, level_type, `key`, `value`, time_created, time_updated)
            VALUES ( :service_id, :scope_type, :level_type, :key, :value, :time_created, :time_updated)');
        $statement->execute( [
            ':service_id' => $this->_scope->getServiceId(),
            ':scope_type' => $this->_scope->getScopeType(),
            ':level_type' => $this->_scope->getLevelType(),
            ':key'        => $this->_scope->getKey(),
            ':value'      => json_encode( $data, JSON_PRETTY_PRINT),
            ':time_created' => $timeCreated,
            ':time_updated' => time(),
        ]);
    }

    private function _getTimeCreatedOfExistingServiceParam()
    {
        $statement = $this->_conn->getConnection()->prepare('SELECT `time_created` FROM service_params WHERE
            service_id = :service_id AND scope_type = :scope_type AND level_type = :level_type AND `key` = :key');

        $this->_logger->debug( 'Fetching time created of params for ['.$this->_scope.'] ...');

        $statement->execute([
            ':service_id' => $this->_scope->getServiceId(),
            ':scope_type' => $this->_scope->getScopeType(),
            ':level_type' => $this->_scope->getLevelType(),
            ':key'        => $this->_scope->getKey(),
        ]);

        $row = $statement->fetch( \PDO::FETCH_ASSOC);

        if ( !$row) {
            $this->_logger->debug( 'Returning new time created ...');
            return time();
        }

        $timeCreated = intval($row['time_created']);
        if ($timeCreated === 0) {
            $timeCreated = time();
        }

        $this->_logger->debug( 'Returning time created ['.$row['time_created'].'] ...');
        return $timeCreated;
    }
}
