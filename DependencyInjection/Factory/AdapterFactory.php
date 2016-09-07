<?php

/*
 * This file is part of HeriJobQueueBundle.
 *
 * (c) Alexandre Mogère
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Heri\Bundle\JobQueueBundle\DependencyInjection\Factory;

use Heri\Bundle\JobQueueBundle\Adapter as Adapter;
use Heri\Bundle\JobQueueBundle\Exception\InvalidAdapterDefinitionException;

/**
 * Adapter factory.
 */
class AdapterFactory
{
    public static $em;

    /**
     * Create staticly desired Adapter.
     *
     * @param string $type   Type of Adapter to create
     * @param array  $config Configuration container
     *
     * @return AdapterInterface Adapter instance
     */
    public static function get($type, $config)
    {
        $instance = null;
        $options = [];

        switch ($type) {

            case 'doctrine':
                $instance = new Adapter\DoctrineAdapter($options);
                $instance->em = self::$em;
                break;

            case 'amqp':
                $instance = new Adapter\AmqpAdapter($config['amqp_connection']);
                break;

            default:
                throw new InvalidAdapterDefinitionException();
        }

        return $instance;
    }
}
