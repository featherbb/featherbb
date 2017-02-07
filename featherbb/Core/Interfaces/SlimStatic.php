<?php
namespace FeatherBB\Core\Interfaces;

class SlimStatic
{
    /**
    * Boots up SlimStatic by registering its proxies with Statical.
    *
    * @param \Slim\Slim $slim
    * @return \Statical\Manager
    */
    public static function boot(\Slim\App $slim)
    {
        // set Slim application for syntactic-sugar proxies
        SlimSugar::$slim = $slim;

        // create a new Manager
        $manager = new \Statical\Manager();

        // Add proxies that use the Slim instance
        $aliases = ['Feather', 'Config', 'Route', 'Router', 'ForumEnv', 'ForumSettings', 'User'];
        static::addInstances($aliases, $manager, $slim);

        // Add special-case Slim container instance
        $aliases = ['Container'];
        static::addInstances($aliases, $manager, $slim->getContainer());

        // Add services that are resolved out of the Slim container
        static::addServices($manager, $slim);

        return $manager;
    }

    /**
    * Adds instances to the Statical Manager
    *
    * @param string[] $aliases
    * @param \Statical\Manager $manager
    * @param object $instance
    */
    protected static function addInstances($aliases, $manager, $instance)
    {
        foreach ($aliases as $alias) {
            $proxy = __NAMESPACE__.'\\'.$alias;
            $manager->addProxyInstance($alias, $proxy, $instance);
        }
    }

    /**
    * Adds services to the Statical Manager
    *
    * @param \Statical\Manager $manager
    * @param \Slim\Slim $slim
    */
    protected static function addServices($manager, $slim)
    {
        $services = [
            'Input' => 'request',
            'Request' => 'request',
            'Response' => 'response',
            'View' => 'template'
        ];

        $container = $slim->getContainer();

        foreach ($services as $alias => $id) {
            $proxy = __NAMESPACE__.'\\'.$alias;
            $manager->addProxyService($alias, $proxy, $container, $id);
        }
    }
}
