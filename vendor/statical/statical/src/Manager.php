<?php

/*
 * This file is part of the Statical package.
 *
 * (c) John Stevenson <john-stevenson@blueyonder.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

 namespace Statical;

 use Statical\Input;

 class Manager
 {
    /**
    * The static classes to proxy.
    *
    * @var array
    */
    protected $registry = array();

    /**
    * The alias manager
    *
    * @var \Statical\AliasManager
    */
    protected $aliasManager;

    /**
    * Whether the class is to be treated as a singleton.
    *
    * @var bool
    */
    public static $singleton = false;

    /**
    * Constructor.
    *
    * Creating more than one instance could lead to unpredictable behaviour.
    * To enforce a singleton pattern call the makeSingleton() method.
    *
    * The optional params allow the default way to start the Manager to be
    * overriden.
    *
    * By default the service will be enabled and a proxy to ourself will be
    * created, aliased as Statical and available in any namespace. To modify
    * this behaviour pass one of the following to $bootMode:
    *
    *   'enable'    - the service is enabled
    *   'none'      - no action, the implementation must call enable()
    *
    * The namespacing feature is on by default, but can be disabled here if it
    * is not required, or suitable.
    *
    * @param mixed $bootMode Either 'enable' or 'none' if specified
    * @param boolean $namespacing Whether namespacing should be allowed
    * @throws RuntimeException if we we have been set as a singleton
    */
    public function __construct($bootMode = null, $namespacing = true)
    {
        if (static::$singleton) {
            throw new \RuntimeException(__CLASS__ . ' has been set as a singleton.');
        }

        BaseProxy::setResolver($this);
        $this->aliasManager = new AliasManager($namespacing);
        $this->boot($bootMode);
    }

    /**
    * Registers ourself as a proxy, aliased as Statical, and enables the service.
    *
    * @param mixed $namespace Either a string or array of namespaces
    * @return void
    */
    public function addProxySelf($namespace = null)
    {
        $this->addProxyInstance('Statical', 'Statical\\StaticalProxy', $this);

        if ($namespace) {
            $this->addNamespace('Statical', $namespace);
        }

        $this->enable();
    }

    /**
    * Adds a service as a proxy target
    *
    * If $id is null then the lower-cased alias will be used.
    *
    * @param string $alias The statical name you call
    * @param string $proxy The namespaced proxy class
    * @param mixed $container Reference to a container
    * @param mixed $id The id of the target in the container
    * @param mixed $namespace Optional namespace
    */
    public function addProxyService($alias, $proxy, $container, $id = null, $namespace = null)
    {
        $proxy = Input::checkNamespace($proxy);
        $container = Input::checkContainer($container);
        $id = $id ?: strtolower($alias);

        $this->addProxy($proxy, $id, $container);
        $this->aliasManager->add($proxy, $alias);

        if ($namespace) {
            $this->addNamespace($alias, $namespace);
        }
    }

    /**
    * Adds an instance or closure as a proxy target
    *
    * @param string $alias The statical name you call
    * @param string $proxy The namespaced proxy class
    * @param mixed $target The target instance or closure
    * @param mixed $namespace Optional namespace
    */
    public function addProxyInstance($alias, $proxy, $target, $namespace = null)
    {
        $proxy = Input::checkNamespace($proxy);

        if (!is_object($target)) {
            throw new \InvalidArgumentException('Target must be an instance or closure.');
        }

        $this->addProxy($proxy, null, $target);
        $this->aliasManager->add($proxy, $alias);

        if ($namespace) {
            $this->addNamespace($alias, $namespace);
        }
    }

    /**
    * Adds a namespace for a single, or all aliases
    *
    * The $alias can either be a single value or the wildcard '*' value, which
    * allows any registered alias in the namespace.
    *
    * Namespace can either be a single string value or an array of values and
    * are formatted as follows:
    *
    *   'App'     - the alias can be called in the App namespace
    *   'App\\*'  - the alias can be called in the App or descendant namespaces
    *   '*'       - the alias can be called in any namespace
    *
    * @param string $alias
    * @param mixed $namespace
    */
    public function addNamespace($alias, $namespace)
    {
        $namespace = Input::formatNamespace($namespace);
        $this->aliasManager->addNamespace($alias, $namespace);
    }

    /**
    * Adds a namespace group for a single, or all aliases
    *
    * The $alias can either be a single value or the wildcard '*' value, which
    * allows any registered alias in the namespace.
    *
    * The group can be one of the following:
    *
    *   'name'  - the alias can be called in the $namespace
    *   'path'  - the alias can be called in the $namespace and any descendants
    *   'any'   - the alias can be called in any namespace
    *
    * Namespace can either be a single string value, an array of values, or
    * missing in the case of group 'any'.
    *
    * @param string $group
    * @param string $alias
    * @param mixed $namespace
    */
    public function addNamespaceGroup($group, $alias, $namespace = null)
    {
        $namespace = Input::formatNamespace($namespace, $group);
        $this->aliasManager->addNamespace($alias, $namespace);
    }

    /**
    * Enables static proxying by registering the autoloader.
    *
    * Ensures that the autoloader is always at the end of the stack. This can
    * be useful if another part of the code base adds its own autoloader after
    * the Statical one. Keeping the autoloader at the end reduces the risk of
    * conflicts with other libraries.
    *
    * This method can be called multiple times, because it does nothing if
    * the autoloader is in its correct place.
    *
    * @return void
    */
    public function enable()
    {
        $this->aliasManager->enable();
    }

    /**
    * Disables static proxying.
    *
    * Included for completeness, in case there is a requirement to stop
    * Statical. The autoloader is removed from the stack, but this will not
    * affect any classes that have already been loaded.
    *
    * @return void
    */
    public function disable()
    {
        $this->aliasManager->disable();
    }

    /**
    * Enforces a singeton pattern on the class.
    *
    * Subsequent attempts to instantiate this class will throw an exception.
    *
    * @return void
    */
    public function makeSingleton()
    {
        static::$singleton = true;
    }

    /**
    * Returns the target instance of the static proxy.
    *
    * This function is called from Statical\BaseProxy to resolve proxy targets.
    *
    * @param string $class
    * @throws RuntimeException
    * @return mixed
    */
    public function getProxyTarget($class)
    {
        if (!isset($this->registry[$class])) {
            throw new \RuntimeException($class.' not registered as a static proxy.');
        }

        if ($id = $this->registry[$class]['id']) {
            return call_user_func_array($this->registry[$class]['target'], array($id));
        } else {

            if ($closure = $this->registry[$class]['closure']) {
                $this->registry[$class]['target'] = $closure();
                $this->registry[$class]['closure'] = null;
            }

            return $this->registry[$class]['target'];
        }
    }

    /**
    * Adds a proxy to the registry array
    *
    * Since this is called internally it assumes that the inputs are correct.
    *
    * @param string $proxy
    * @param string $id
    * @param mixed $target
    * @return void
    */
    protected function addProxy($proxy, $id, $target)
    {
        $callee = $target instanceof \Closure ? null : $target;
        $closure = $callee ? null : $target;

        $this->registry[$proxy] = array(
            'id' => $id,
            'target' => $callee,
            'closure' => $closure
        );
    }

    /**
    * Starts the Manager with either the default or more restricted settings.
    *
    * @param mixed $bootMode
    * $return void
    */
    protected function boot($bootMode)
    {
        switch ($bootMode) {
            case 'enable':
                // Enable the service
                $this->enable();
                break;
            case 'none':
                // Do nothing, the implementation will deal with it
                break;
            default:
                // Enable the service and register ourself in any namespace
                $this->addProxySelf('*');
                break;
        }
    }
 }
