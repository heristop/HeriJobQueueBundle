<?php

namespace Heri\Bundle\JobQueueBundle\Tests;

use Symfony\Component\HttpKernel\Kernel;

use Doctrine\ORM\Tools\SchemaTool;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Symfony\Component\HttpKernel\AppKernel
     */
    protected static $kernel;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    public function setUp()
    {
        require_once __DIR__.'/Fixtures/app/AppKernel.php';

        // boot the AppKernel in the test environment and with the debug.
        $this->kernel = new \Heri\Bundle\JobQueueBundle\Tests\AppKernel('test', true);
        $this->kernel->boot();

        // store the container and the entity manager in test case properties
        $this->container = $this->kernel->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();

        // build the schema for sqlite
        $this->generateSchema();

        parent::setUp();
    }

    public function tearDown()
    {
        @unlink(__DIR__ . '/Fixtures/app/sqlite.db');

        // shutdown the kernel.
        $this->kernel->shutdown();

        parent::tearDown();
    }

    protected function generateSchema()
    {
        // get the metadata of the application to create the schema.
        $metadata = $this->getMetadata();

        if (!empty($metadata)) {
            // create SchemaTool
            $tool = new SchemaTool($this->em);
            $tool->createSchema($metadata);
        } else {
            throw new Doctrine\DBAL\Schema\SchemaException('No Metadata Classes to process.');
        }
    }

    /**
     * Overwrite this method to get specific metadata.
     *
     * @return Array
     */
    protected function getMetadata()
    {
        return $this->em->getMetadataFactory()->getAllMetadata();
    }
}
