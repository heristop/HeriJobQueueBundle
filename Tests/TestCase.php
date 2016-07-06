<?php

namespace Heri\Bundle\JobQueueBundle\Tests;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Filesystem\Filesystem;
use Doctrine\ORM\Tools\SchemaTool;

abstract class TestCase extends \PHPUnit_Extensions_Database_TestCase
{
    /**
     * @var Symfony\Component\HttpKernel\AppKernel
     */
    protected $kernel;

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

        // Boot the AppKernel in the test environment and with the debug.
        $this->kernel = new \Heri\Bundle\JobQueueBundle\Tests\AppKernel('test', true);
        $this->kernel->boot();

        $this->deleteTmpDir();

        // Store the container and the entity manager in test case properties
        $this->container = $this->kernel->getContainer();
        $this->em = $this->container->get('doctrine')->getManager();

        parent::setUp();
    }

    public function getConnection()
    {
        // Retrieve PDO instance
        $pdo = $this->em->getConnection()->getWrappedConnection();

        // Clear Doctrine to be safe
        $this->em->clear();

        // Schema Tool to process our entities
        $tool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $classes = $this->em->getMetaDataFactory()->getAllMetaData();

        // Drop all classes and re-build them for each test case
        $tool->dropSchema($classes);
        $tool->createSchema($classes);

        // Pass to PHPUnit
        return $this->createDefaultDBConnection($pdo, 'db_name');
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/Fixtures/data.xml');
    }

    public function tearDown()
    {
        // Shutdown the kernel.
        $this->kernel->shutdown();

        parent::tearDown();
    }

    protected function generateSchema()
    {
        // Get the metadata of the application to create the schema.
        $metadata = $this->getMetadata();

        if (!empty($metadata)) {
            // Create SchemaTool
            $tool = new SchemaTool($this->em);
            $tool->createSchema($metadata);
        } else {
            throw new Doctrine\DBAL\Schema\SchemaException('No Metadata classes to process.');
        }
    }

    /**
     * Overwrite this method to get specific metadata.
     *
     * @return array
     */
    protected function getMetadata()
    {
        return $this->em->getMetadataFactory()->getAllMetadata();
    }

    protected function deleteTmpDir()
    {
        if (!file_exists($dir = sys_get_temp_dir().'/'.Kernel::VERSION)) {
            return;
        }
        $fs = new Filesystem();
        //$fs->remove($dir);
    }
}
