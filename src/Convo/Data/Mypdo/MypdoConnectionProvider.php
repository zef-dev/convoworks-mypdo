<?php declare(strict_types=1);

namespace Convo\Data\Mypdo;


class MypdoConnectionProvider
{
    
    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;
    
	/**
	 * @var \PDO
	 */
	private $_conn;
	
	/**
	 * @var string
	 */
	private $_host;
	
	/**
	 * @var string
	 */
	private $_name;
	
	/**
	 * @var string
	 */
	private $_user;
	
	/**
	 * @var string
	 */
	private $_password;

	public function __construct( \Psr\Log\LoggerInterface $logger, $host, $name, $user, $pass)
	{
	    $this->_logger     =   $logger;
	    $this->_host       =   $host;
	    $this->_name       =   $name;
	    $this->_user       =   $user;
	    $this->_password   =   $pass;
	}
    
	/**
	 * @return \PDO
	 */
	public function getConnection() {
	    if ( empty( $this->_conn)) {
	        $str_conn   =   'mysql:host='.$this->_host.';dbname='.$this->_name;
	        $this->_logger->info( 'Connecting to ['.$str_conn.']');
	        $this->_conn = new \PDO( $str_conn, $this->_user, $this->_password);
	        $this->_conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	    }
	    
	    return $this->_conn;
	}
	
	
	// UTIL
	public function __toString()
	{
	    return get_class( $this).'['.$this->_host.']['.$this->_name.']['.$this->_user.']';
	}


}
