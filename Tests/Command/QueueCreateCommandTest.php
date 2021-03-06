<?php

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Heri\Bundle\JobQueueBundle\Tests\TestCase;
use Heri\Bundle\JobQueueBundle\Command\QueueCreateCommand;
use Heri\Bundle\JobQueueBundle\Command\Queue;

class QueueCreateCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = new Application($this->kernel);
        $application->add(new QueueCreateCommand());

        $command = $application->find('jobqueue:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'queue-name' => 'my:queue1',
            '--timeout' => 10,
        ], ['interactive' => false]);

        $this->assertRegExp('/Queue "my:queue1" created/', $commandTester->getDisplay(), 'Created queue');

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'queue-name' => 'my:queue1',
            '--timeout' => 10,
        ], ['interactive' => false]);

        $this->assertRegExp('/Queue "my:queue1" updated/', $commandTester->getDisplay(), 'Updated queue');
    }
}
