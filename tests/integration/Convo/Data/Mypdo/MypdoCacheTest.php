<?php


use Convo\Core\Util\EchoLogger;
use PHPUnit\Framework\TestCase;

class MypdoCacheTest extends TestCase
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var \Convo\Data\Mypdo\MypdoConnectionProvider
     */
    private $_myPdoConnectionProvider;

    /**
     * @var \Convo\Data\Mypdo\MypdoCache
     */
    private $_myPdoCache;

    public function setUp(): void
    {
        $this->_logger  	            =   new EchoLogger();
        $this->_myPdoConnectionProvider = new \Convo\Data\Mypdo\MypdoConnectionProvider($this->_logger, $GLOBALS['DB_HOST'], $GLOBALS['DB_DBNAME'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
        $this->_myPdoCache              = new \Convo\Data\Mypdo\MypdoCache($this->_logger, $this->_myPdoConnectionProvider);
    }

    /**
     * @dataProvider validKeyValuePairsProvider
     * @param $key
     * @param $value
     */
    public function testSingleCacheSetValueWithTtl($key, $value) {
        $ttl = 10000;

        $hasSet = $this->_myPdoCache->set($key, $value, $ttl);
        $cacheValue = $this->_myPdoCache->get($key);
        $this->assertEquals(true, $hasSet);
        $this->assertEquals($value, $cacheValue);
    }

    /**
     * @dataProvider validKeyValuePairsProvider
     * @param $key
     * @param $value
     */
    public function testSingleCacheSetValueWithoutTtl($key, $value) {
        $hasSet = $this->_myPdoCache->set($key, $value);
        $cacheValue = $this->_myPdoCache->get($key);
        $this->assertEquals(true, $hasSet);
        $this->assertEquals(null, $cacheValue);
    }

    /**
     * @dataProvider wrongKeysProvider
     * @param $wrongKey
     */
    public function testSingleCacheSetValueWithCorruptKey($wrongKey) {
        $key = $wrongKey;

        $value = 'some_test_value';
        $hasSet = $this->_myPdoCache->set($key, $value);
        $cacheValue = $this->_myPdoCache->get($key);
        $this->assertEquals(false, $hasSet);
        $this->assertEquals(null, $cacheValue);
    }

    public function testSingleCacheSetValueAndExpired() {
        $key = 'some_test_key';
        $ttl = 1;

        $value = 'some_test_value';
        $hasSet = $this->_myPdoCache->set($key, $value, $ttl);

        sleep(2);

        $cacheValue = $this->_myPdoCache->get($key);
        $this->assertEquals(true, $hasSet);
        $this->assertEquals(null, $cacheValue);
    }

    public function testSingleCacheSetValueAndNotExpired() {
        $key = 'some_test_key';
        $ttl = 3;

        $value = 'some_test_value';
        $hasSet = $this->_myPdoCache->set($key, $value, $ttl);

        sleep(2);

        $cacheValue = $this->_myPdoCache->get($key);
        $this->assertEquals(true, $hasSet);
        $this->assertEquals($cacheValue, $cacheValue);
    }

    public function testMultipleCacheSetValueWithTtl() {
        // set multiple values
        $hasSet = $this->_myPdoCache->setMultiple(['key_1' => 'value_1', 'key_2' => 'value_2', 'key_3' => 'value_3'], 10000);
        $this->assertEquals(true, $hasSet);

        // check if values are present by calling _myPdoCache->getMultiple
        $cachedValues = $this->_myPdoCache->getMultiple(['key_1', 'key_2', 'key_3']);
        $this->assertCount(3, $cachedValues);

        // check if values deleted via _myPdoCache->deleteMultiple
        $hasDeleted = $this->_myPdoCache->deleteMultiple(['key_1', 'key_2', 'key_3']);
        $this->assertEquals(true, $hasDeleted);

        // check if deleted values are no longer present
        $hasDeleted = $this->_myPdoCache->deleteMultiple(['key_1', 'key_2', 'key_3']);
        $this->assertEquals(false, $hasDeleted);

    }

    public function testMultipleCacheSetValueWithoutTtl() {
        // set multiple values
        $hasSet = $this->_myPdoCache->setMultiple(['key_1' => 'value_1', 'key_2' => 'value_2', 'key_3' => 'value_3']);
        $this->assertEquals(true, $hasSet);

        // check if values are present by calling _myPdoCache->getMultiple
        $cachedValues = $this->_myPdoCache->getMultiple(['key_1', 'key_2', 'key_3']);
        $this->assertCount(0, $cachedValues);

        // check if values deleted via _myPdoCache->deleteMultiple
        $hasDeleted = $this->_myPdoCache->deleteMultiple(['key_1', 'key_2', 'key_3']);
        $this->assertEquals(true, $hasDeleted);
        $hasDeleted = $this->_myPdoCache->deleteMultiple(['key_1', 'key_2', 'key_3']);
        $this->assertEquals(false, $hasDeleted);
    }

    public function testWrongMultipleCacheSetValue() {
        // with ttl
        $hasSet = $this->_myPdoCache->setMultiple(5, 10000);
        $this->assertEquals(false, $hasSet);

        $hasSet = $this->_myPdoCache->setMultiple('test', 10000);
        $this->assertEquals(false, $hasSet);

        $hasSet = $this->_myPdoCache->setMultiple('t', 10000);
        $this->assertEquals(false, $hasSet);

        // without ttl
        $hasSet = $this->_myPdoCache->setMultiple(5);
        $this->assertEquals(false, $hasSet);

        $hasSet = $this->_myPdoCache->setMultiple('test');
        $this->assertEquals(false, $hasSet);

        $hasSet = $this->_myPdoCache->setMultiple('t');
        $this->assertEquals(false, $hasSet);
    }

    public function testClearCache() {
        // set multiple values
        $hasSet = $this->_myPdoCache->setMultiple(['key_1' => 'value_1', 'key_2' => 'value_2', 'key_3' => 'value_3']);
        $this->assertEquals(true, $hasSet);

        $cacheCleared = $this->_myPdoCache->clear();
        $this->assertEquals(true, $cacheCleared);

        $values = $this->_myPdoCache->getMultiple(['key_1', 'key_2', 'key_3']);
        $this->assertCount(0, $values);
    }

    public function testDeleteSingle() {
        $key = 'some_test_key';
        $ttl = 10000;

        $value = 'some_test_value';
        $this->_myPdoCache->set($key, $value, $ttl);

        $hasDeleted = $this->_myPdoCache->delete($key);
        $this->assertEquals(true, $hasDeleted);

        $hasDeleted = $this->_myPdoCache->delete($key);
        $this->assertEquals(false, $hasDeleted);
    }

    /**
     * @dataProvider validKeyValuePairsProvider
     * @param $key
     * @param $value
     */
    public function testHasCache($key, $value) {
        $ttl = 10000;

        // nothing was set
        $has = $this->_myPdoCache->has($key);
        $this->assertEquals(false, $has);

        // an cache record was set
        $this->_myPdoCache->set($key, $value, $ttl);
        $has = $this->_myPdoCache->has($key);
        $this->assertEquals(true, $has);

        // an expired cache record was set
        $this->_myPdoCache->set($key, $value);
        $has = $this->_myPdoCache->has($key);
        $this->assertEquals(false, $has);
    }

    public function wrongKeysProvider()
    {
        return [
            ['some_test_key \n _and something not good'],
            ['some_test_key \r\n _and something not good'],
            ['some_test_key \t _and something not good like table'],
            ['${bolVal}'],
            ['${result.value}'],
            [null],
            [[0 => 1]],
            [['0' => 1]],
            [[]]
        ];
    }

    public function validKeyValuePairsProvider()
    {
        return [
            ['some_test_key', ''],
            ['some_test_key', 'some_test_value'],
            ['some_test_key', 'ć—Ąćś¬čŞžă�Żă‚Źă�‹ă‚Šă�ľă�™'],
            ['some_test_key', 'ă‚Źă�‹ă‚‹'],
            ['some_test_key', 'ă‚Źă�‹ă‚‰ă�Şă�„'],
            ['some_test_key', 'ă‚Źă�‹ă‚Šă�ľă�™'],
            ['some_test_key', 'ă‚Źă�‹ă‚Šă�ľă�›ă‚“'],
            ['some_test_key', 2],
            ['some_test_key', 1.5],
            ['some_test_key', []],
            ['some_test_key', ['Peter'=> 35, 'Ben'=> 37, 'Joe'=> 43]],
            ['some_test_key', [1 => "a", "1"  => "b", 1.5  => "c", true => "d",]],
            ['some_test_key', ['some_another_key' => 'some_another_value']],
            ['some_test_key', ['some_another_key' => ['some_another_key' => 'some_value']]],
            ['some_test_key', null]
        ];
    }

    protected function tearDown(): void
    {
        // $this->_logger->info('Clearing cache...');
        // $this->_myPdoCache->clear();
    }
}
