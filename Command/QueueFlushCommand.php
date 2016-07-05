<?php

/**
 * This file is part of HeriJobQueueBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueueFlushCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('jobqueue:flush')
            ->setDescription('Delete all of your failed jobs')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $this->getContainer()->get('jobqueue');

        if ($queue->flush()) {
            $output->writeLn('<info>Cleaned exceptions</info>');
        } else {
            $output->writeLn('<info>Flush failed</info>');
        }
    }
}
