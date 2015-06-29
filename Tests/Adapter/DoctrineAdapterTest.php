<?php

use Heri\Bundle\JobQueueBundle\Tests\TestCase;
use Heri\Bundle\JobQueueBundle\Adapter as Adapter;
use Heri\Bundle\JobQueueBundle\Service\QueueService;
use Heri\Bundle\JobQueueBundle\Command\QueueListenCommand;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;

class DoctrineAdapterTest extends TestCase
{
    /**
     * @var QueueService
     */
    protected $queue;

    /**
     * @var string
     */
    protected $queueName = 'my:queue1';

    /**
     * @var integer
     */
    protected $maxMessages = 1;

    /**
     * @var boolean
     */
    protected $verbose = true;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        if (!class_exists('Doctrine\ORM\EntityManager')) {
            $this->markTestSkipped('Doctrine not installed');
        }

        $adapter = new Adapter\DoctrineAdapter(array());
        $adapter->em = $this->em;

        $this->queue = new QueueService($this->container->get('monolog.logger.doctrine'));
        $this->queue->adapter = $adapter;
        $this->queue->attach($this->queueName);

        $application = new Application($this->kernel);
        $application->add(new QueueListenCommand());

        $command = $application->find('jobqueue:listen');
        $this->queue->setCommand($command);

        if ($this->verbose) {
            $this->queue->setOutput(new ConsoleOutput());
        }
    }

    public function testPushAndReceive()
    {
        $queue = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Queue')
            ->findOneByName($this->queueName);
        $this->assertNotNull($queue, 'Queue created');

        // Queue list command
        $command1 = array(
            'command' => 'list'
        );
        $this->queue->push($command1);

        $messages = $this->getMessages();
        $this->assertEquals(1, count($messages), 'Count number of messages');

        $message1 = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Message')
            ->find(1);
        $this->assertEquals($command1, json_decode($message1->getBody(), true), 'Verify encoded message in table');
        $this->assertEquals(false, $message1->getEnded(), 'Message1 no ended');
        $this->assertEquals(false, $message1->getFailed(), 'Message1 no failed');

        // Queue demo:great command
        $command2 = array(
            'command'   => 'demo:great',
            'argument'  => array(
                'name'   => 'Alexandre',
                '--yell' => true
            )
        );
        $this->queue->push($command2);

        $message2 = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Message')
            ->find(2);
        $this->assertEquals($command2, json_decode($message2->getBody(), true), 'Verify encoded message in table');
        $this->assertEquals(false, $message2->getEnded(), 'Message2 no ended');
        $this->assertEquals(false, $message2->getFailed(), 'Message2 no failed');

        $messages = $this->getMessages();
        $this->assertEquals(2, count($messages), '2 pending messages to handle');

        // Run list command
        try {

            $this->queue->receive($this->maxMessages);

            $exceptions = $this->getMessageLogs();
            $this->assertEquals(0, count($exceptions), 'No exception logged');

            $messages = $this->getMessages();
            $this->assertEquals(1, count($messages), '1 pending message not yet handled');

        } catch (\Exception $e) {

            $this->fail($e->getMessage());

        }

        // Run demo:great command
        try {

            $this->queue->receive($this->maxMessages);

        } catch (\Exception $e) {

            $this->assertRegExp('/There are no commands defined in the "demo" namespace/', $e->getMessage(), 'Command not found');

        }

        $messages = $this->getMessages();
        $this->assertEquals(1, count($messages), '1 pending message locked');

        $message2 = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Message')
            ->find(2);
        $this->assertEquals(false, $message2->getEnded(), 'Message2 no ended');
        $this->assertEquals(false, $message2->getFailed(), 'Message2 failed');

        $exceptions = $this->getMessageLogs();
        $this->assertEquals(1, count($exceptions), '1 exception logged');

        $exception = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\MessageLog')
            ->find(1);
        $this->assertRegExp('/There are no commands defined in the "demo" namespace/', $exception->getLog(), 'Logged exception in database');
    }

    public function testDuplicatedMessages()
    {
        $queue1 = $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\Queue')
            ->findOneByName($this->queueName . '1');
        $this->assertNotNull($queue1, 'Queue created');

        // Queue 1 list command
        $command1 = array(
            'command' => 'list',
        );

        // Push $command1
        $this->queue->push($command1);
        $messages = $this->getMessages($queue1);
        $this->assertEquals(1, count($messages), 'Count number of messages');

        // Re Push same command $command1
        $this->queue->push($command1);
        $messages = $this->getMessages($queue1);
        $this->assertEquals(1, count($messages), 'Count number of messages');

        // Push $command2
        $command2 = array(
            'command' => 'list2',
        );
        $this->queue->push($command2);
        $messages = $this->getMessages($queue1);
        $this->assertEquals(2, count($messages), 'Count number of messages');
    }

    public function testCountMessages()
    {
        $count1 = $this->queue->countMessages();

        // Queue list command
        $command1 = array(
            'command' => 'list'
        );
        $this->queue->push($command1);

        $count2 = $this->queue->countMessages();

        $this->assertEquals($count1 + 1 , $count2, 'countMessage retrieve added message');
    }

    protected function getMessages()
    {
        // Search for all messages inside our timeout
        $query = $this->em->createQuery("
            SELECT m
            FROM Heri\Bundle\JobQueueBundle\Entity\Message m
            LEFT JOIN m.queue q
            WHERE (q.name = :queue_name)
        ");
        $query->setParameter('queue_name', $this->queueName);

        return $query->getResult();
    }

    protected function getMessageLogs()
    {
        return $this->em
            ->getRepository('Heri\Bundle\JobQueueBundle\Entity\MessageLog')
            ->findAll();
    }

}
