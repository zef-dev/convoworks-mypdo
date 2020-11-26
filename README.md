# Mysql service data layer for Convoworks

This library contains mysql implementations for `\Convo\Core\IServiceDataProvider`, `\Convo\Core\IServiceParamsFactory` and `\Convo\Core\IServiceParamsFactory` Convoworks interfaces which serves for storing service related data.


## Usage

If you are not using DI, you can initialize them like this:

``` $logger = new \Psr\Log\NullLogger();
    $pdoConnectionProvider = new \Convo\Data\Mypdo\MypdoConnectionProvider( $logger, 'host', 'dbName', 'username', 'password');
    $convoServiceParamsFactory = new \Convo\Data\Mypdo\MypdoServiceParamsFactory( $logger, $pdoConnectionProvider);
    $convoServiceDataProvider = new \Convo\Data\Mypdo\MypdoServiceDataProvider( $logger, $pdoConnectionProvider);
```
    

## Database

Check the docs folder for .sql create database script and Mysql Workbench database model.
