<?php

/**
* Copyright (C) 2015-2016 FeatherBB
* based on code by Christian Metz - MetzWeb Networks
* API Documentation: https://github.com/cosenary/Simple-PHP-Cache
* License: BSD http://www.opensource.org/licenses/bsd-license.php
*
* FeatherBB Cache class
* Usage : $cache = new \FeatherBB\Cache(array('name' => , 'path' =>, 'extension' =>));
*/

namespace FeatherBB\Core;

class Cache
{
    /**
    * @var array
    */
    protected $settings;

    /**
    * @var array
    */
    protected $cache;

    /**
    * @var array
    */
    protected $filenames;

    /**
    * Default constructor
    *
    * @param string|array [optional] $config
    * @return void
    */
    public function __construct($config = [])
    {
        if (!is_array($config)) {
            $config = ['name' => (string) $config];
        }
        $this->settings = array_merge(self::getDefaultSettings(), $config);
        $this->setCache($this->settings['name']);
        $this->setCachePath($this->settings['path']);
        $this->setCacheExtension($this->settings['extension']);
    }

    /**
    * Return default settings
    *
    * @return array
    */
    protected static function getDefaultSettings()
    {
        return ['name' => 'default',
        'path' => 'cache/',
        'extension' => '.cache'];
    }

    /**
    * Check whether data is associated with a key
    *
    * @param string $key
    * @return boolean
    */
    public function isCached($key)
    {
        if ($cachedData = $this->_loadCache()) {
            if (isset($cachedData[$key])) {
                if (!$this->_isExpired($cachedData[$key]['time'], $cachedData[$key]['expire'])) {
                    return true;
                }
            }
        }
        return false; // If cache file doesn't exist or cache is empty or key doesn't exist in array, key isn't cached
    }

    /**
    * Store data in the cache
    *
    * @param string $key
    * @param mixed $data
    * @param integer [optional] $expires
    * @return self
    */
    public function store($key, $data, $expires = 0)
    {
        $new_data = [
            'time' => time(),
            'expire' => (int) $expires,
            'data' => serialize($data)
        ];

        $cache = $this->_loadCache();
        if (is_array($cache)) {
            $cache[(string) $key] = $new_data;
        } else {
            $cache = [(string) $key => $new_data];
        }
        $this->_saveCache($cache);
        return $this;
    }

    /**
    * Retrieve cached data by key
    *
    * @param string $key
    * @param boolean [optional] $timestamp
    * @return string
    */
    public function retrieve($key)
    {
        $key = (string) $key;
        if ($cache = $this->_loadCache()) {
            if (isset($cache[$key])) {
                if (!$this->_isExpired($cache[$key]['time'], $cache[$key]['expire'])) {
                    return unserialize($cache[$key]['data']);
                }
            }
        }
        return null;
    }

    /**
    * Retrieve all cached data
    *
    * @param boolean [optional] $meta
    * @return array | null
    */
    public function retrieveAll($raw = false)
    {
        if ($cache = $this->_loadCache()) {
            if (!$raw) {
                $results = [];
                foreach ($cache as $key => $value) {
                    $results[$key] = unserialize($value['data']);
                }
                return $results;
            } else {
                return $cache;
            }
        }
        return null;
    }

    /**
    * Delete cached entry by its key
    *
    * @param string $key
    * @return object
    */
    public function delete($key)
    {
        $key = (string) $key;
        if ($cache = $this->_loadCache()) {
            if (isset($cache[$key])) {
                unset($cache[$key]);
                $this->_saveCache($cache);
                return $this;
            }
        }
        throw new \Exception("Error: delete() - Key '{$key}' not found.");
    }

    /**
    * Erase all expired entries
    *
    * @return object
    */
    public function deleteExpired()
    {
        $cache = $this->_loadCache();
        if (is_array($cache)) {
            $i = 0;
            $cache = array_map(function ($value) {
                        if (!$this->_isExpired($value['time'], $value['expire'])) {
                            ++$i;
                            return $value;
                        }
                        });
            if ($i > 0) {
                $this->_saveCache($cache);
            }
        }
        return $this;
    }

    /**
    * Flush all cached entries
    * @return object
    */
    public function flush()
    {
        $this->cache = null; // Purge cache
        $this->_saveCache([]);
        return $this;
    }

    /**
    * Increment key
    * @return object
    */
    public function increment($key)
    {
        $key = (string) $key;
        if ($cache = $this->_loadCache()) {
            if (isset($cache[$key])) {
                $tmp = unserialize($cache[$key]['data']);
                if (is_numeric($tmp)) {
                    ++$tmp;
                    $cache[$key]['data'] = serialize($tmp);
                    $this->_saveCache($cache);
                    return $this;
                }
            }
        }
        throw new \Exception("Error: increment() - Key '{$key}' not found.");
    }

    /**
    * Decrement key
    * @return object
    */
    public function decrement($key)
    {
        $key = (string) $key;
        if ($cache = $this->_loadCache()) {
            if (isset($cache[$key])) {
                $tmp = unserialize($cache[$key]['data']);
                if (is_numeric($tmp)) {
                    --$tmp;
                    $cache[$key]['data'] = serialize($tmp);
                    $this->_saveCache($cache);
                    return $this;
                }
            }
        }
        throw new \Exception("Error: decrement() - Key '{$key}' not found.");
    }

    /**
    * Load cache
    * @return cache if existing or not null / null otherwise
    */
    protected function _loadCache()
    {
        if (!is_null($this->cache))
        return $this->cache;

        if (file_exists($this->getCacheFile())) {
            $this->cache = json_decode(file_get_contents($this->getCacheFile()), true);
            return $this->cache;
        }
        return null;
    }

    /**
    * Save cache file
    * @param $dataArray
    */
    protected function _saveCache(array $data)
    {
        $this->cache = $data; // Save new data in object to avoid useless I/O access
        return file_put_contents($this->getCacheFile(), json_encode($data));
    }

    /**
    * Check whether a timestamp is still in the duration
    *
    * @param integer $timestamp
    * @param integer $expiration
    * @return boolean
    */
    protected function _isExpired($timestamp, $expiration)
    {
        if ($expiration !== 0) {
            return (time() - $timestamp > $expiration);
        }
        return false;
    }

    /**
    * Check if a writable cache directory exists and if not create a new one
    *
    * @return boolean
    */
    protected function _checkCacheDir()
    {
        if (!is_dir($this->getCachePath()) && !mkdir($this->getCachePath(), 0775, true)) {
            throw new \Exception('Unable to create cache directory ' . $this->getCachePath());
        } elseif (!is_readable($this->getCachePath()) || !is_writable($this->getCachePath())) {
            if (!chmod($this->getCachePath(), 0775)) {
                throw new \Exception($this->getCachePath() . ' must be readable and writable');
            }
        }
        return true;
    }

    /**
    * Getters and setters
    */

    /**
    * Get the cache directory path
    *
    * @return string
    */
    public function getCacheFile()
    {
        if (!isset($this->filenames[$this->settings['name']])) {
            if ($this->_checkCacheDir()) {
                $filename = preg_replace('/[^0-9a-z\.\_\-]/i', '', strtolower($this->settings['name']));
                $this->filenames[$this->settings['name']] = $this->settings['path'] . sha1($filename) . $this->settings['extension'];
            }
        }
        return $this->filenames[$this->settings['name']];
    }

    /**
    * Cache path Setter
    *
    * @param string $path
    * @return object
    */
    public function setCachePath($path)
    {
        $this->settings['path'] = $path;
    }

    /**
    * Cache path Getter
    *
    * @return string
    */
    public function getCachePath()
    {
        return $this->settings['path'];
    }

    /**
    * Cache name Setter
    *
    * @param string $name
    * @return object
    */
    public function setCache($name)
    {
        $this->cache = null; // Purge cache as we change cache
        $this->settings['name'] = $name;
    }

    /**
    * Cache name Getter
    *
    * @return void
    */
    public function getCache()
    {
        return $this->settings['path'];
    }

    /**
    * Cache file extension Setter
    *
    * @param string $ext
    * @return object
    */
    public function setCacheExtension($ext)
    {
        $this->settings['extension']= $ext;
    }

    /**
    * Cache file extension Getter
    *
    * @return string
    */
    public function getCacheExtension()
    {
        return $this->settings['extension'];
    }

    /**
    * Settings Getter
    *
    * @return array
    */
    public function getSettings()
    {
        return $this->settings;
    }
}
