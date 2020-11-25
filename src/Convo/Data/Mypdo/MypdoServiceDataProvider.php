<?php declare(strict_types=1);

namespace Convo\Data\Mypdo;

use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\IAdminUser;
use Convo\Core\AbstractServiceDataProvider;
use Convo\Core\Rest\NotAuthorizedException;
use Convo\Core\DataItemNotFoundException;
use Convo\Core\IServiceDataProvider;

class MypdoServiceDataProvider extends AbstractServiceDataProvider
{

	/**
	 * @var MypdoConnectionProvider
	 */
	private $_conn;

	public function __construct( \Psr\Log\LoggerInterface $logger, MypdoConnectionProvider $conn)
	{
	    parent::__construct( $logger);
		$this->_conn		=	$conn;
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::getAllServices()
	 */
	public function getAllServices( \Convo\Core\IAdminUser $user) 
	{
	    $statement =   $this->_conn->getConnection()->query( 'SELECT * FROM service_data');
	    $all       =   array();
	    while ( $row = $statement->fetch( \PDO::FETCH_ASSOC)) 
	    {
	        $serviceMeta = $this->getServiceMeta( $user, $row['service_id']);
	        if ($this->_checkServiceOwner( $user, $serviceMeta)) {
	            $all[]		=	$serviceMeta;
	        }
	    }

		return $all;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::createNewService()
	 */
	public function createNewService( \Convo\Core\IAdminUser $user, $serviceName, $serviceAdmins, $isPrivate, $workflowData)
	{
	    $service_id                 =   $this->_generateIdFromName( $serviceName);
	    
	    // META
	    $meta_data					=	$this->_getDefaultMeta( $user, $service_id, $serviceName);
	    $meta_data['service_id']	=	$service_id;
	    $meta_data['name']			=	$serviceName;
	    $meta_data['owner']			=	$user->getEmail();
	    $meta_data['admins']        =   $serviceAdmins;
	    $meta_data['is_private']    =   $isPrivate;
	    
	    // WORKFLOW
	    $service_data					=   array_merge( IServiceDataProvider::DEFAULT_WORKFLOW, $workflowData);
	    $service_data['name']   		=	$serviceName;
	    $service_data['service_id']		=	$service_id;
	    
	    $service_data['time_updated']             =   time();
	    $service_data['intents_time_updated']     =   time();
	    
	    
	    $statement = $this->_conn->getConnection()->prepare( 'INSERT INTO service_data ( service_id, workflow, meta, config)
            VALUES (:service_id, :workflow, :meta, :config)');
	    
	    $statement->execute([
	        ':service_id' => $service_id,
	        ':workflow' => json_encode( $service_data, JSON_PRETTY_PRINT),
	        ':meta' => json_encode( $meta_data, JSON_PRETTY_PRINT),
	        ':config' => json_encode( [], JSON_PRETTY_PRINT),
	    ]);
	    
	    return $service_id;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::deleteService()
	 */
	public function deleteService( \Convo\Core\IAdminUser $user, $serviceId)
	{
	    $service_meta = $this->getServiceMeta($user, $serviceId);
	    
	    $is_owner = $user->getEmail() === $service_meta['owner'];
	    $is_admin = in_array($user->getEmail(), $service_meta['admins']);
	    
	    if (!($is_owner || $is_admin)) {
	        throw new \Exception('User ['.$user->getName().']['.$user->getEmail().'] is not allowed to delete skill ['.$serviceId.']');
	    }
	    
	    $statement = $this->_conn->getConnection()->prepare('DELETE from service_params WHERE service_id = :service_id');
	    $statement->execute([':service_id' => $serviceId]);
	    
	    $statement = $this->_conn->getConnection()->prepare('DELETE from service_releases WHERE service_id = :service_id');
	    $statement->execute([':service_id' => $serviceId]);
	    
	    $statement = $this->_conn->getConnection()->prepare('DELETE from service_versions WHERE service_id = :service_id');
	    $statement->execute([':service_id' => $serviceId]);
	    
	    $statement = $this->_conn->getConnection()->prepare('DELETE from service_data WHERE service_id = :service_id');
	    $statement->execute([':service_id' => $serviceId]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::getServiceData()
	 */
	public function getServiceData( \Convo\Core\IAdminUser $user, $serviceId, $versionId)
	{
	    $this->_logger->debug( 'Fetching service ['.$serviceId.']['.$versionId.'] data');
	    $serviceMeta = $this->getServiceMeta( $user, $serviceId);
	    if( !$this->_checkServiceOwner( $user, $serviceMeta)) {
	        $errorMessage = "User [" . $user->getUsername() . "] is not authorized to open the service [" . $serviceId ."]";
	        throw new NotAuthorizedException( $errorMessage);
	    }
	    
	    if ( $versionId === IPlatformPublisher::MAPPING_TYPE_DEVELOP) {
	        $statement = $this->_conn->getConnection()->prepare('SELECT workflow FROM service_data where service_id = :service_id');
	        $statement->execute([':service_id' => $serviceId]);
	    } else {
	        $statement = $this->_conn->getConnection()->prepare('SELECT workflow FROM service_versions where service_id = :service_id AND version_id = :version_id');
	        $statement->execute([':service_id' => $serviceId, ':version_id' => $versionId]);
	    }
	    
	    while ($row = $statement->fetch( \PDO::FETCH_ASSOC)) {
	        $this->_logger->debug( 'handling row ['.print_r( json_decode( $row['workflow'], true), true).'] data');
	        return array_merge( IServiceDataProvider::DEFAULT_WORKFLOW, json_decode( $row['workflow'], true));
	    }
	    
	    throw new DataItemNotFoundException( 'Service data ['.$serviceId.']['.$versionId.'] not found');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::saveServiceData()
	 */
	public function saveServiceData( \Convo\Core\IAdminUser $user, $serviceId, $data)
	{
	    $data['time_updated']   =   time();
	    
	    $statement = $this->_conn->getConnection()->prepare('UPDATE service_data SET workflow = :workflow where service_id = :service_id');
	    $statement->execute([':service_id' => $serviceId, ':workflow' => json_encode( $data, JSON_PRETTY_PRINT)]);
	    
	    return $data;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::getServiceMeta()
	 */
	public function getServiceMeta( \Convo\Core\IAdminUser $user, $serviceId, $versionId=null)
	{
	    if ( $versionId && $versionId !== IPlatformPublisher::MAPPING_TYPE_DEVELOP) {
	        $statement = $this->_conn->getConnection()->prepare('SELECT service_id, version_id, release_id, version_tag, time_created, time_updated
                FROM service_versions where service_id = :service_id AND version_id = :version_id');
	        $statement->execute([':service_id' => $serviceId, ':version_id' => $versionId]);
	        $row = $statement->fetch( \PDO::FETCH_ASSOC);
	        if ( !$row) {
	            throw new DataItemNotFoundException( 'Service meta ['.$serviceId.']['.$versionId.'] not found');
	        }
	        $row['time_created'] = intval( $row['time_created']);
	        $row['time_updated'] = intval( $row['time_updated']);
	        return $row;
	    }
	    
	    $statement = $this->_conn->getConnection()->prepare('SELECT * FROM service_data where service_id = :service_id');
	    $statement->execute([':service_id' => $serviceId]);
	    $row = $statement->fetch( \PDO::FETCH_ASSOC);
	    if ( !$row) {
            throw new DataItemNotFoundException( 'Service meta ['.$serviceId.'] not found');
	    }
	    $row['meta']   =   json_decode( $row['meta'], true);
	    return array_merge( IServiceDataProvider::DEFAULT_META, $row['meta']);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::saveServiceMeta()
	 */
	public function saveServiceMeta( \Convo\Core\IAdminUser $user, $serviceId, $meta)
	{
	    $meta['time_updated']   =   time();
	    $statement = $this->_conn->getConnection()->prepare('UPDATE service_data SET meta = :meta where service_id = :service_id');
	    $statement->execute([':service_id' => $serviceId, ':meta' => json_encode( $meta, JSON_PRETTY_PRINT)]);
	    
	    return $meta;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::markVersionAsRelease()
	 */
	public function markVersionAsRelease( \Convo\Core\IAdminUser $user, $serviceId, $versionId, $releaseId)
	{
	    $statement = $this->_conn->getConnection()->prepare('UPDATE service_versions SET release_id = :release_id where service_id = :service_id AND version_id = :version_id');
	    $statement->execute([':service_id' => $serviceId, ':release_id' => $releaseId, ':version_id' => $versionId]);
	    
	    return $this->getServiceMeta($user, $serviceId, $versionId);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::getAllServiceVersions()
	 */
	public function getAllServiceVersions( \Convo\Core\IAdminUser $user, $serviceId) {
	    
	    $statement =   $this->_conn->getConnection()->query( 'SELECT * FROM service_versions');
	    $all       =   array();
	    while ( $row = $statement->fetch( \PDO::FETCH_ASSOC)) {
	        $all[]		=	$row['version_id'];
	    }
	    
	    return $all;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::createServiceVersion()
	 */
	public function createServiceVersion(\Convo\Core\IAdminUser $user, $serviceId, $workflow, $config, $versionTag=null)
	{
	    $version_id	=	$this->_getNextServiceVersion( $serviceId);
	    $this->_logger->debug( 'Got new version ['.$version_id.'] for service ['.$serviceId.']');
	    
	    $statement = $this->_conn->getConnection()->prepare( 'INSERT INTO service_versions
            ( service_id, version_id, version_tag, workflow, config, time_created, time_updated)
            VALUES (:service_id, :version_id, :version_tag, :workflow, :config, :time_created, :time_updated)');
	    
	    $statement->execute([
	        ':service_id' => $serviceId,
	        ':version_id' => $version_id,
	        ':version_tag' => $versionTag,
	        ':workflow' => json_encode( $workflow, JSON_PRETTY_PRINT),
	        ':config' => json_encode( $config, JSON_PRETTY_PRINT),
	        ':time_updated' => time(),
	        ':time_created' => time(),
	    ]);
	    
	    return $version_id;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::createRelease()
	 */
	public function createRelease( IAdminUser $user, $serviceId, $platformId, $type, $stage, $alias, $versionId)
	{
	    $release_id    =   $this->_getNextReleseId( $serviceId);
	    
	    $statement     =   $this->_conn->getConnection()->prepare( 'INSERT INTO service_releases
            ( service_id, release_id, platform_id, version_id, type, stage, alias, time_created, time_updated)
            VALUES (:service_id, :release_id, :platform_id, :version_id, :type, :stage, :alias, :time_created, :time_updated)');
	    
	    $statement->execute([
	        ':service_id' => $serviceId,
	        ':release_id' => $release_id,
	        ':platform_id' => $platformId,
	        ':version_id' => $versionId,
	        ':type' => $type,
	        ':stage' => $stage,
	        ':alias' => $alias,
	        ':time_created' => time(),
	        ':time_updated' => time(),
	    ]);
	    
	    return $release_id;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::getReleaseData()
	 */
	public function getReleaseData( IAdminUser $user, $serviceId, $releaseId)
	{
	    $statement =   $this->_conn->getConnection()->prepare( 'SELECT * FROM service_releases WHERE service_id = :service_id AND release_id = :release_id');
	    $statement->execute( ['service_id'=>$serviceId, 'release_id'=>$releaseId]);
	    
	    if ( $statement->rowCount()) {
	        $row = $statement->fetch( \PDO::FETCH_ASSOC);
	        $row['time_created'] = intval( $row['time_created']);
	        $row['time_updated'] = intval( $row['time_updated']);
	        return $row;
	    }
	    
	    throw new \Convo\Core\DataItemNotFoundException( 'Service ¸release ['.$serviceId.']['.$releaseId.'] not found');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::promoteRelease()
	 */
	public function promoteRelease( \Convo\Core\IAdminUser $user, $serviceId, $releaseId, $type, $stage) {
	    $statement = $this->_conn->getConnection()->prepare('UPDATE service_releases SET type = :type, stage = :stage, time_updated = :time_updated
               WHERE service_id = :service_id AND release_id = :release_id');
	    $statement->execute([':service_id' => $serviceId, ':type' => $type, ':stage' => $stage, ':time_updated' => time()]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::setReleaseVersion()
	 */
	public function setReleaseVersion( \Convo\Core\IAdminUser $user, $serviceId, $releaseId, $versionId) {
	    $statement = $this->_conn->getConnection()->prepare('UPDATE service_releases SET version_id = :version_id, time_updated = :time_updated
               WHERE service_id = :service_id AND release_id = :release_id');
	    $statement->execute([':service_id' => $serviceId, ':version_id' => $versionId, ':release_id' => $releaseId, ':time_updated' => time()]);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::getServicePlatformConfig()
	 */
	public function getServicePlatformConfig( \Convo\Core\IAdminUser $user, $serviceId, $versionId)
	{
	    if ( $versionId === IPlatformPublisher::MAPPING_TYPE_DEVELOP) {
	        $statement =   $this->_conn->getConnection()->prepare( 'SELECT `config` FROM service_data WHERE service_id = :service_id');
	        $statement->execute( [':service_id'=>$serviceId]);
	    } else {
	        $statement =   $this->_conn->getConnection()->prepare( 'SELECT `config` FROM service_versions WHERE service_id = :service_id AND version_id = :version_id');
	        $statement->execute( [':service_id'=>$serviceId, ':version_id'=>$versionId]);
	    }
	    
	    if ( $statement->rowCount()) {
	        $row = $statement->fetch( \PDO::FETCH_ASSOC);
	        return json_decode( $row['config'], true);
	    }
	    
	    if ( $versionId === IPlatformPublisher::MAPPING_TYPE_DEVELOP) {
	        return [];
	    }
	    
	    // if there is version, config has to be present
	    throw new \Convo\Core\DataItemNotFoundException( 'Service config ['.$serviceId.']['.$versionId.']');
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\IServiceDataProvider::updateServicePlatformConfig()
	 */
	public function updateServicePlatformConfig( \Convo\Core\IAdminUser $user, $serviceId, $config)
	{
	    $statement = $this->_conn->getConnection()->prepare('UPDATE service_data SET config = :config where service_id = :service_id');
	    $statement->execute([':service_id' => $serviceId, ':config' => json_encode( $config, JSON_PRETTY_PRINT)]);
	}


	// COMMON
	private function _getNextServiceVersion( $serviceId) 
	{
	    $statement =   $this->_conn->getConnection()->prepare( 'SELECT version_id FROM service_versions WHERE service_id = :service_id ORDER BY version_id DESC LIMIT 0,1');
	    $statement->execute([':service_id' => $serviceId]);
	    if ( $statement->rowCount()) {
	        $row = $statement->fetch( \PDO::FETCH_ASSOC);
	        $curr  =   intval( $row['version_id']);
	    } else {
	        $curr  =   0;
	    }
	    
	    $curr++;
	    return sprintf('%08d', $curr);
	}
	
	
	private function _getNextReleseId( $serviceId) 
	{
	    $statement =   $this->_conn->getConnection()->prepare( 'SELECT release_id FROM service_releases WHERE service_id = :service_id ORDER BY release_id DESC LIMIT 0,1');
	    $statement->execute([':service_id' => $serviceId]);
	    if ( $statement->rowCount()) {
	        $row = $statement->fetch( \PDO::FETCH_ASSOC);
	        $curr  =   intval( $row['release_id']);
	    } else {
	        $curr  =   0;
	    }
	    
	    $curr++;
	    return sprintf('%08d', $curr);
	}
	
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}


}
