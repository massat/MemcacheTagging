<?php
/**
 * A Memcached client tagging supported.
 * @author massat <mail@massat.jp>
 * @package MemcachedTagging
 * @link http://github.com/massat/MemcacheTagging
 */
class MemcacheTagging
{
    const SEPARATOR = ':';
    const KEY_INDICATOR_VALUE = 'v';
    const KEY_INDICATOR_META  = 'm';
    const KEY_INDICATOR_TAG   = 't';
    
    private static $defaultLifetime = 3600;
    
    /**
     * @var Memcache
     */
    private $cache;
    
    private $namespace;
    private $lifetime;
    
    private $valueKeyPrefix;
    private $metaKeyPrefix;
    private $tagKeyPrefix;
    
    /**
     * returns an instance
     *
     * available option
     *   * lifetime
     *   * namespace
     *   * servers
     *     * host
     *     * port
     *     * persistent
     *     * weight
     *
     * @param array $options
     * @return MemcacheTagging
     */
    public static function getInstance(array $options = array())
    {
        return new MemcacheTagging($options);
    }
    
    /**
     * saves a value in the cache with given key and tags
     *
     * @param string $key
     * @param mixed $value
     * @param array $tags
     * @param int $lifetime
     */
    public function set($key, $value, array $tags = array(), $lifetime = null)
    {
        $lifetime = is_null($lifetime) ? $this->lifetime : $lifetime;
        $expire   = is_null($lifetime) ? null            : time() + $lifetime;
        
        $this->setValue($key, $value, $expire);
        $this->setValueMetaData($key, $tags, $expire);
        
        foreach($tags as $tag) {
            $this->addTagMetaData($key, $tag);
        }
    }
    
    /**
     * returns a cached content for a given key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->getValue($key, $default);
    }
    
    /**
     * returns cached values for given keys
     *
     * @param array $keys
     * @return array
     */
    public function getMany(array $keys)
    {
        $values = array();
        foreach ($this->cache->get(array_map(create_function('$k', 'return "'.$this->valueKeyPrefix.'".$k;'), $keys)) as $key => $value)
        {
          $values[str_replace($this->valueKeyPrefix, '', $key)] = $value;
        }
    
        return $values;
    }
    
    /**
     * returns tags associated with a given key
     *
     * @param string $key
     * @return array
     */
    public function getTags($key)
    {
        $tags = array();
        if(
            ($meta = $this->getValueMetaData($key))
            &&
            isset($meta['tags'])
        ) {
            $tags = $meta['tags'];
        }
        
        return $tags;
    }
    
    /**
     * returns values for a given tag
     *
     * @param string $tag
     * @return array
     */
    public function getByTag($tag)
    {
        $values = array();
        if(
            ($meta = $this->getTagMetaData($tag))
            &&
            isset($meta['keys'])
            &&
            $meta['keys']
        ) {
            $values = $this->getMany($meta['keys']);
        }
        
        return $values;
    }
    
    /**
     * returns true if there is a cache for the given key.
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return $this->getValue($key, false) !== false;
    }
    
    /**
     * returns a last modified timestamp for a given key
     *
     * @param string $key
     * @return int|null
     */
    public function getLastModified($key)
    {
        return ($metaData = $this->getValueMetaData($key)) ? $metaData['last_modified'] : null;
    }
    
    /**
     * returns an expiring timestamp for a given key
     *
     * @param string $key
     * @return int|null
     */
    public function getTimeout($key)
    {
        return ($metaData = $this->getValueMetaData($key)) ? $metaData['expire'] : null;
    }
    
    /**
     * delete a cached content for a given key
     *
     * @param string $key
     */
    public function delete($key)
    {
        $tags = $this->getTags($key);
        
        $this->deleteValue($key);
        $this->deleteValueMetaData($key);
        foreach($tags as $tag) {
            $this->removeFromTagMetaData($key, $tag);
        }
    }
    
    /**
     * delete cached values for a given tag
     *
     * @param string $tag
     */
    public function deleteByTag($tag)
    {
        if($tagMetaData = $this->getTagMetaData($tag)) {
            foreach($tagMetaData['keys'] as $key) {
                $this->delete($key);
            }
        }
    }
    
    /**
     * clear all cached contents
     *
     * @return boolean
     */
    public function flush()
    {
        return $this->cache->flush();
    }
    
    /********************************
     * private methods
     ********************************/
    
    /**
     * set a value with key
     *
     * @param string $key
     * @param string $value
     * @param int $expire
     * @return boolean
     */
    private function setValue($key, $value, $expire = null)
    {
        $valueKey = $this->valueKeyPrefix . $key;
        return $this->_set($valueKey, $value, $expire);
    }
    
    /**
     * set a value-meta-data
     *
     * @param string $key
     * @param array $tags
     * @param int $expire
     * @return boolean
     */
    private function setValueMetaData($key, array $tags = array(), $expire = null)
    {
        $metaKey = $this->metaKeyPrefix . $key;
        $metaData = array(
            'last_modified' => time(),
            'expire'        => $expire,
            'tags'          => $tags
        );
        
        return $this->_set($metaKey, $metaData, $expire);
    }
    
    /**
     * add a tag-meta-data
     *
     * @param string $key
     * @param string $tag
     * @return boolean
     */
    private function addTagMetaData($key, $tag)
    {
        $tagKey = $this->tagKeyPrefix . $tag;
        $metaData = $this->getTagMetaData($tag);
        
        if(!in_array($key, $metaData['keys'])) {
            $metaData['keys'][] = $key;
            return $this->_set($tagKey, $metaData, 0);
        }
        
        return false;
    }
    
    /**
     * get a value with associated key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getValue($key, $default = null)
    {
        $valueKey = $this->valueKeyPrefix . $key;
        return $this->_get($valueKey, $default);
    }
    
    /**
     * get a value-meta-data associated with key
     *
     * @param string $key
     * @return array
     */
    private function getValueMetaData($key)
    {
        $metaKey = $this->metaKeyPrefix . $key;
        return $this->_get($metaKey, array());
    }
    
    /**
     * get a tag-meta-data
     *
     * @param string $tag
     * @return array
     */
    private function getTagMetaData($tag)
    {
        $tagKey = $this->tagKeyPrefix . $tag;
        return $this->_get($tagKey, array('keys' => array()));
    }
    
    /**
     * delete a value associated with key
     *
     * @param string $key
     * @return boolean
     */
    private function deleteValue($key)
    {
        $valueKey = $this->valueKeyPrefix . $key;
        return $this->_delete($valueKey);
    }
    
    /**
     * delete a value-meta-data associated with key
     *
     * @param string $key
     * @return boolean
     */
    private function deleteValueMetaData($key)
    {
        $metaKey  = $this->metaKeyPrefix  . $key;
        return $this->_delete($metaKey);
    }
    
    /**
     * delete a tag-meta-data
     *
     * @param string $tag
     * @return boolean
     */
    private function deleteTagMetaData($tag)
    {
        $tagKey = $this->tagKeyPrefix . $tag;
        return $this->_delete($tagKey);
    }
    
    /**
     * remove key from tag-meta-data
     *
     * @param string $key
     * @param string $tag
     */
    private function removeFromTagMetaData($key, $tag)
    {
        $tagKey = $this->tagKeyPrefix . $tag;
        $tagMetaData = $this->getTagMetaData($tag);
        if(in_array($key, $tagMetaData['keys'])) {
            unset($tagMetaData['keys'][$key]);
            $this->_set($tagKey, $tagMetaData, 0);
        }
    }
    
    /**
     * wraps Memcache::set
     *
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return boolean
     * @link http://www.php.net/manual/ja/function.memcache-set.php
     */
    private function _set($key, $value, $expire = null)
    {
        if(false !== $this->cache->replace($key, $value, null, $expire)) {
            return true;
        }
        
        return $this->cache->set($key, $value, null, $expire);
    }
    
    /**
     * wraps Memcache::get
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     * @link http://www.php.net/manual/ja/function.memcache-get.php
     */
    private function _get($key, $default = null)
    {
        return (false !== ($value = $this->cache->get($key))) ? $value : $default;
    }
    
    /**
     * wraps Memcache::delete
     *
     * @param string $key
     * @return boolean
     * @link http://www.php.net/manual/ja/function.memcache-delete.php
     */
    private function _delete($key)
    {
        return $this->cache->delete($key);
    }
    
    /**
     * constructor
     *
     * * available option
     *   * lifetime
     *   * namespace
     *   * servers
     *     * host
     *     * port
     *     * persistent
     *     * weight
     *
     * @param array $options
     */
    private function __construct(array $options = array())
    {
        if(!class_exists('Memcache')) {
            throw new Exception('Memcache module is not available.');
        }
        
        // Memcache instance
        $this->cache = new Memcache();
        
        // a guide of the version of Memcache module
        $version = method_exists($this->cache, 'addServer') ? 2 : 1;
        
        $this->lifetime  = isset($options['lifetime'])  ? $options['lifetime']  : self::$defaultLifetime;
        $this->namespace = isset($options['namespace']) ? $options['namespace'] : md5(dirname(__FILE__));
        
        $this->valueKeyPrefix = $this->namespace . self::SEPARATOR . self::KEY_INDICATOR_VALUE . self::SEPARATOR;
        $this->metaKeyPrefix  = $this->namespace . self::SEPARATOR . self::KEY_INDICATOR_META  . self::SEPARATOR;
        $this->tagKeyPrefix   = $this->namespace . self::SEPARATOR . self::KEY_INDICATOR_TAG   . self::SEPARATOR;
        
        // register memcached servers
        $servers = isset($options['servers'])
            ? $options['servers']
            : array(
                  array(
                      'host'       => 'localhost',
                      'port'       => 11211,
                      'persistent' => true
                  )
              );
              
        if(($version < 2) && (count($servers) > 1)) {
            throw new Exception('The version of Memcache module is required to be lather than 2.0.0 for multi servers.');
        }
        
        foreach($servers as $server)
        {
            $host       = isset($server['host'])       ? $server['host']       : 'localhost';
            $port       = isset($server['port'])       ? $server['port']       : 11211;
            $persistent = isset($server['persistent']) ? $server['persistent'] : true;
            $weight     = isset($server['weight'])     ? $server['weight']     : 1;
            
            if($version >= 2) {
                if(!$this->cache->addServer($host, $port, $persistent, $weight)) {
                    throw new Exception(sprintf("Can't connect to Memcache Server (%s:%s)", $host, $port));
                }
            } else {
                $method = $persistent ? 'pconnect' : 'connect';
                if(!$this->cache->$method($host, $port)) {
                    throw new Exception(sprintf("Can't connect to Memcache Server (%s:%s)", $host, $port));
                }
            }
        }
    }
}