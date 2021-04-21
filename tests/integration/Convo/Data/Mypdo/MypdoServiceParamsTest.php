<?php

use Convo\Core\Params\IServiceParamsScope;
use Convo\Core\Params\RequestParamsScope;
use Convo\Core\Publish\IPlatformPublisher;
use Convo\Core\Util\EchoLogger;
use PHPUnit\Framework\TestCase;

class MypdoServiceParamsTest extends TestCase
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var string
     */
    private $_serviceId = 'test-service';

    /**
     * @var \Convo\Core\IAdminUser
     */
    private $_adminUser;

    /**
     * @var \Convo\Data\Mypdo\MypdoConnectionProvider
     */
    private $_myPdoConnectionProvider;

    /**
     * @var \Convo\Data\Mypdo\MypdoServiceDataProvider
     */
    private $_myPdoServiceDataProvider;

    /**
     * @var \Convo\Data\Mypdo\MypdoServiceParams
     */
    private $_myPdoServiceParams;

    public function setUp(): void
    {
        $this->_logger  	=   new EchoLogger();
        $this->_adminUser = new \Convo\Core\Admin\AdminUser(1, 'test01', 'Test 01', 'test01@zef-dev.com');
        $this->_myPdoConnectionProvider = new \Convo\Data\Mypdo\MypdoConnectionProvider($this->_logger, $GLOBALS['DB_HOST'], $GLOBALS['DB_DBNAME'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
        $this->_myPdoServiceDataProvider = new \Convo\Data\Mypdo\MypdoServiceDataProvider($this->_logger, $this->_myPdoConnectionProvider);
        $this->_prepareTestService();
    }

    public function testSetServiceParams()
    {
        $defaultTextRequest = new \Convo\Core\Adapters\ConvoChat\DefaultTextCommandRequest($this->_serviceId, 'installation-2', 'session-2', 'request-2', 'some text here');
        $scope		=	new RequestParamsScope( $defaultTextRequest, IServiceParamsScope::SCOPE_TYPE_INSTALLATION, IServiceParamsScope::LEVEL_TYPE_SERVICE);
        $this->_myPdoServiceParams = new \Convo\Data\Mypdo\MypdoServiceParams($this->_logger, $scope, $this->_myPdoConnectionProvider);

        $parameterName = 'test';
        $parameterValue = ['greetings' => 'hi for the first time'];
        $this->_myPdoServiceParams->setServiceParam($parameterName, $parameterValue);
        $this->assertEquals($parameterValue, $this->_myPdoServiceParams->getServiceParam($parameterName));
        sleep(2);

        $parameterValue = ['greetings' => 'hi for the second time'];
        $this->_myPdoServiceParams->setServiceParam($parameterName, $parameterValue);
        $this->assertEquals($parameterValue, $this->_myPdoServiceParams->getServiceParam($parameterName));
        sleep(3);

        $parameterValue = ['greetings' => 'hi for the third time'];
        $this->_myPdoServiceParams->setServiceParam($parameterName, $parameterValue);
        $this->assertEquals($parameterValue, $this->_myPdoServiceParams->getServiceParam($parameterName));
        sleep(4);

        $parameterValue = ['greetings' => 'bye'];
        $this->_myPdoServiceParams->setServiceParam($parameterName, $parameterValue);
        $this->assertEquals($parameterValue, $this->_myPdoServiceParams->getServiceParam($parameterName));

        $this->_myPdoServiceDataProvider->deleteService($this->_adminUser, $this->_serviceId);
    }

    private function _prepareTestService() {
        $existingService = null;

        try {
            $existingService = $this->_myPdoServiceDataProvider->getServiceData($this->_adminUser, $this->_serviceId, IPlatformPublisher::MAPPING_TYPE_DEVELOP);
        } catch (\Convo\Core\DataItemNotFoundException $e) {
            $this->_logger->warning('Service with id [' . $this->_serviceId . '] was not found.');
        }

        if ($existingService === null) {
            $this->_logger->info('Going to create new service with id [' . $this->_serviceId . ']');
            $this->_myPdoServiceDataProvider->createNewService($this->_adminUser, 'Test Service', 'en', 'en-US', ['en-US'], false, [], []);
        }
    }
}
