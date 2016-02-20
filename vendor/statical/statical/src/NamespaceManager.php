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

 class NamespaceManager
 {
    /**
    * Namespaces to modify lazy loading.
    *
    * @var array
    */
    protected $namespaces = array();

    /**
    * Adds a namespace
    *
    * @param string $alias
    * @param string[] $namespace
    */
    public function add($alias, array $namespace)
    {
        $alias = Input::checkAlias($alias);
        $props = $this->getNamespace($alias, true);

        foreach ($namespace as $ns) {
            $group = $this->getNamespaceGroup($ns);

            if ('any' === $group) {
                $props = array('any' => true);
                break;
            } else {
                // trim trailing * from path pattern
                $ns = 'path' === $group ? rtrim($ns, '*') : $ns;
                $props[$group][] = $ns;
            }
        }

        $this->setNamespace($alias, $props);
    }

    /**
    * Returns true if a matching namespace is found.
    *
    * @param string $alias
    * @param string $class
    * @return bool
    */
    public function match($alias, $class)
    {
        foreach (array('*', $alias) as $key) {
            if ($props = $this->getNamespace($key)) {
                if ($this->matchGroup($props, $alias, $class)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
    * Returns true if a namespace entry is matched.
    *
    * @param array $props
    * @param string $alias
    * @param string $class
    * @return bool
    */
    protected function matchGroup($props, $alias, $class)
    {
        if ($props['any']) {
            return true;
        }

        foreach (array('path', 'name') as $group) {
            if ($this->matchClass($props[$group], $group, $alias, $class)) {
                return true;
            }
        }

        return false;
    }

    /**
    * Returns true if a class matches a namespace item
    *
    * @param array $array
    * @param string $group
    * @param string $alias
    * @param string $class
    */
    protected function matchClass($array, $group, $alias, $class)
    {
        $match = false;

        foreach ($array as $test) {

            if ('path' === $group) {
                $match = 0 === strpos($class, $test);
            } else {
                $match = $test.'\\'.$alias === $class;
            }

            if ($match) {
                break;
            }
        }

        return $match;
    }

    /**
    * Returns the namespace groups for an alias.
    *
    * Returns either an empty array if the alias is not found and $default is
    * false, or an array containing all namespace groups with any found values.
    *
    * @param string $alias
    * @param bool $default
    * @return array
    */
    protected function getNamespace($alias, $default = false)
    {
        $result = isset($this->namespaces[$alias]) ? $this->namespaces[$alias] : array();

        if ($result || $default) {
            $result = array_merge($this->getDefaultGroups(), $result);
        }

        return $result;
    }

    /**
    * Adds a namespace array group to the namespaces array.
    *
    * If the group is an array, duplicate entries are removed. Empty groups
    * are removed from the final array entry.
    *
    * @param string $alias
    * @param array $props
    * @return void
    */
    protected function setNamespace($alias, $props)
    {
        array_walk($props, function (&$value) {
            if (is_array($value)) {
                $value = array_unique($value);
            }
        });

        $this->namespaces[$alias] = array_filter($props);
    }

    /**
    * Returns the group name for the namespace input type.
    *
    * @param string $namespace
    * @return string
    */
    protected function getNamespaceGroup($namespace)
    {
        $namespace = Input::checkNamespace($namespace);

        if ('*' === substr($namespace, -1)) {
            if ('*' === $namespace) {
                $group = 'any';
            } else {
                $group = 'path';
            }
        } else {
            $group = 'name';
        }

        return $group;
    }

    /**
    * Returns an array of default groups.
    *
    * @return array
    */
    protected function getDefaultGroups()
    {
        return array(
            'any' => false,
            'path' => array(),
            'name' => array()
        );
    }
 }
