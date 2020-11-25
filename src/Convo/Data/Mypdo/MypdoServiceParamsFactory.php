<?php declare(strict_types=1);

namespace Convo\Data\Mypdo;

class MypdoServiceParamsFactory implements \Convo\Core\Params\IServiceParamsFactory
{
    /**
     * @var MypdoConnectionProvider
     */
    private $_conn;
    

	/**
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	/**
	 * @var \Convo\Core\Params\SimpleParams[]
	 */
	private $_params	=	[];

	public function __construct( \Psr\Log\LoggerInterface $logger, MypdoConnectionProvider $conn)
	{
		$this->_logger		=	$logger;
		$this->_conn		=	$conn;
	}

	/**
	 * {@inheritDoc}
	 * @see \Convo\Core\Params\IServiceParamsFactory::getServiceParams()
	 */
	public function getServiceParams( \Convo\Core\Params\IServiceParamsScope $scope) {

		if ( $scope->getScopeType() === \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST) {

			if ( !isset( $this->_params[$scope->getKey()])) {
				$this->_params[$scope->getKey()]	=	new \Convo\Core\Params\SimpleParams();
			}

			return $this->_params[$scope->getKey()];
		}

		return new MypdoServiceParams( $this->_logger, $scope, $this->_conn);
	}


	// UTIL
	public function __toString()
	{
		return get_class( $this);
	}
}
