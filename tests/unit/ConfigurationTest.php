<?php

namespace IU\RedCapEtlModule;

use PHPUnit\Framework\TestCase;

define('USERID', 'testuser');
define('PROJECT_ID', 123);
define('APP_PATH_WEBROOT_FULL', '/var/www/html/redcap/');

class ConfigurationTest extends TestCase
{

    public function setup()
    {
    }

    public function testCreate()
    {
        $config = new Configuration('test');
        $this->assertNotNull($config, 'Object creation test');
        
        $this->assertEquals('test', $config->getName(), 'Configuration name test');
    }
    
    public function testValidation()
    {
        $result = Configuration::validateName('test');
        $this->assertTrue($result, 'Config name "test" test.');
        
        $result = Configuration::validateName('Test Configuration');
        $this->assertTrue($result, 'Config name "Test Configuration" test.');
        
        $exceptionCaught = false;
        try {
            $result = Configuration::validateName('<script>alert(123);</script>');
        } catch (\Exception $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Config name script exception)');
    }
}