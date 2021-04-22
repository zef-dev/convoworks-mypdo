<?php


namespace Convo\Data\Mypdo;


use Psr\SimpleCache\CacheInterface;

class MypdoCache implements CacheInterface
{
    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var MypdoConnectionProvider
     */
    private $_conn;

    public function __construct(\Psr\Log\LoggerInterface $logger, MypdoConnectionProvider $conn)
    {
        $this->_logger =  $logger;
        $this->_conn   =  $conn;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!$this->_isKeyOk($key)) {
            return $default;
        }

        $queryString = 'SELECT `value`, `expires` FROM convoworks_cache WHERE `key` = :key';
        $statement = $this->_conn->getConnection()->prepare($queryString);

        $this->_logger->debug( 'Fetching cached value for ['.$key.'] ...');

        $statement->execute([
            ':key' => $key,
        ]);

        $row = $statement->fetch( \PDO::FETCH_ASSOC);

        if ( !$row) {
            $this->_logger->debug( 'Returning empty ...');
            return $default;
        }

        if (time() > $row['expires']) {
            $this->_logger->debug("Item [$key] has expired. Will treat GET as a cache miss.");
            return $default;
        }

        $this->_logger->debug( 'Returning data ['.$row['value'].'] ...');
        return json_decode( $row['value'], true);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateInterval|int|null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        if (!$this->_isKeyOk($key)) {
            return false;
        }

        $this->_logger->debug('Storing response [' . $key . '][' . json_encode($value) . ']');

        $now = time();
        $expires = 0;

        if ($ttl) {
            $expires = $now + $ttl;
        }

        $queryString = 'REPLACE INTO convoworks_cache (`key`, `value`, time_created, expires)
            VALUES ( :key, :value, :time_created, :time_updated)';
        $statement = $this->_conn->getConnection()->prepare($queryString);

        $statement->execute([
            ':key'          => $key,
            ':value'        => json_encode($value, JSON_PRETTY_PRINT),
            ':time_created' => $now,
            ':time_updated' => $expires,
        ]);

        $this->_logger->debug("Inserted [" . $statement->rowCount() . "] row");

        return $statement->rowCount() > 0;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $queryString = 'DELETE from convoworks_cache WHERE `key` = :key';
        $statement = $this->_conn->getConnection()->prepare($queryString);

        $statement->execute([
            ':key' => $key
        ]);

        $this->_logger->debug("Deleting rows [" . $statement->rowCount() . "]");

        return $statement->rowCount() > 0;
    }

    /**
     * @return bool
     */
    public function clear()
    {
        $statement = $this->_conn->getConnection()->prepare('TRUNCATE TABLE convoworks_cache');
        $hasCleared = $statement->execute();
        $this->_logger->debug("Has Cleared rows [" . $hasCleared . "] .");
        return $hasCleared;
    }

    /**
     * @param iterable $keys
     * @param mixed|null $default
     * @return iterable
     */
    public function getMultiple($keys, $default = null)
    {
        $ret = [];

        if (!is_array($keys)) {
            return $ret;
        }

        $queryString = 'SELECT `value`, `expires` from convoworks_cache WHERE find_in_set(`key`, :keys)';
        $statement = $this->_conn->getConnection()->prepare($queryString);

        $statement->execute([
            ':keys' => implode(',', $keys)
        ]);

        $rows = $statement->fetchAll( \PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $this->_logger->debug( 'Returning empty ...');
            return $ret;
        }

        foreach ($rows as $row) {
            if (time() < $row['expires']) {
                array_push($ret, $row['value']);
            }
        }

        $this->_logger->debug( 'Returning data ['.print_r($ret, true).'] ...');

        return $ret;
    }

    /**
     * @param iterable $values
     * @param \DateInterval|int|null $ttl
     * @return bool
     */
    public function setMultiple($values, $ttl = null)
    {
        $ret = true;

        if (!is_array($values)) {
            return false;
        }

        foreach ($values as $key => $value) {
            $ret = $ret && $this->set($key, $value, $ttl);
        }

        return $ret;
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple($keys)
    {
        if (!is_array($keys)) {
            return false;
        }

        $queryString = 'DELETE from convoworks_cache WHERE find_in_set(`key`, :keys)';
        $statement = $this->_conn->getConnection()->prepare($queryString);

        $statement->execute([
            ':keys' => implode(',', $keys)
        ]);

        $this->_logger->info("Deleting rows [" . $statement->rowCount() . "]");

        return $statement->rowCount() > 0;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        if (!$this->_isKeyOk($key)) {
            return false;
        }

        $queryString = 'SELECT `key`, `expires` FROM convoworks_cache WHERE `key` = :key';
        $statement = $this->_conn->getConnection()->prepare($queryString);

        $this->_logger->debug( 'Fetching cached value for ['.$key.'] ...');

        $statement->execute([
            ':key' => $key,
        ]);

        $row = $statement->fetch( \PDO::FETCH_ASSOC);

        if ( !$row) {
            $this->_logger->debug( 'Returning empty ...');
            return false;
        }

        if (time() > $row['expires']) {
            $this->_logger->debug("Item [$key] has expired. Will treat GET as a cache miss.");
            return false;
        }

        $this->_logger->debug( 'Returning data ['.$row['key'].'] with number of rows [' . $statement->rowCount() . ']');
        return $key === $row['key'];
    }

    private function _isKeyOk($key) {
        $isKeyOk = true;
        $regex = '/[\{\}\(\)\/\\\\@:]/m';

        if (!is_string($key)) {
            return false;
        }

        if (preg_match($regex, $key) === 1) {
            $isKeyOk = false;
        }

        return $isKeyOk;
    }

    // UTIL
    public function __toString()
    {
        return get_class($this);
    }
}
