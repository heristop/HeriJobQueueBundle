<?php

/*
 * This file is part of HeriJobQueueBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class QueueCommand extends ContainerAwareCommand
{
    protected
      $client,
      $input;
    
    protected function configure()
    {
        $this
            ->setName('jobqueue:load')
            ->setDescription('Initialize JobQueue to make synchronizations')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue  = $this->getContainer()->get('jobqueue');
        $config = $this->getContainer()->getParameter('jobqueue.config');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        
        if ($config['enabled'])
        {
            $queue->configure('erp:front', $em);
            $queue->receive(1, $this, $output);
        }
    }
}