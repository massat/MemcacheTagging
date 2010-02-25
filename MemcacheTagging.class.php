<?php

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
     * @param array $options
     * @return MemcacheTagging
     */
    public static function getInstance(array $options = array())
    {
        return new MemcacheTagging($options);
    }
    
    public function set($key, $value, array $tags = array(), $lifetime = null)
    {
        $lifetime = is_null($lifetime) ? $this->lifetime : $lifetime;
        $expire   = is_null($lifetime) ? null            : time() + $lifetime;
        
        $this->setValue($key, $value, $expire);
        $this->setValueMetaData($key, $tags, $expire);
        
        foreach($tags as $tag) {
            $this->addTagMetaData($key, $tag, $expire);
        }
    }
    
    public function get($key, $default = null)
    {
        return $this->getValue($key, $default);
    }
    
    public function getMany(array $keys)
    {
        $values = array();
        foreach ($this->cache->get(array_map(create_function('$k', 'return "'.$this->valueKeyPrefix.'".$k;'), $keys)) as $key => $value)
        {
          $values[str_replace($this->valueKeyPrefix, '', $key)] = $value;
        }
    
        return $values;
    }
    
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
    
    public function delete($key)
    {
        $tags = $this->getTags($key);
        
        $this->deleteValue($key);
        $this->deleteValueMetaData($key);
        foreach($tags as $tag) {
            $this->removeFromTagMetaData($key, $tag);
        }
    }
    
    
    public function deleteByTags(array $tags)
    {
        
    }
    
    public function flush()
    {
        return $this->cache->flush();
    }
    
    ///////////////////////////////////////////////////////////////////////////////////////
    
    private function setValue($key, $value, $expire = null)
    {
        $valueKey = $this->valueKeyPrefix . $key;
        $this->_set($valueKey, $value, $expire);
    }
    
    private function setValueMetaData($key, array $tags = array(), $expire = null)
    {
        $metaKey = $this->metaKeyPrefix . $key;
        $metaData = array(
            'last_modified' => time(),
            'expire'        => $expire,
            'tags'          => $tags
        );
        
        $this->_set($metaKey, $metaData, $expire);
    }
    
    private function addTagMetaData($key, $tag, $expire = null)
    {
        $tagKey = $this->tagKeyPrefix . $tag;
        $metaData = $this->getTagMetaData($tag);
        
        if(!in_array($key, $metaData['keys'])) {
            $metaData['keys'][] = $key;
            $this->_set($tagKey, $metaData, $expire);
        }
    }
    
    private function getValue($key, $default = null)
    {
        $valueKey = $this->valueKeyPrefix . $key;
        return $this->_get($valueKey, $default);
    }
    
    private function getValueMetaData($key)
    {
        $metaKey = $this->metaKeyPrefix . $key;
        return $this->_get($metaKey, array());
    }
    
    private function getTagMetaData($tag)
    {
        $tagKey = $this->tagKeyPrefix . $tag;
        return $this->_get($tagKey, array('keys' => array()));
    }
    
    private function deleteValue($key)
    {
        $valueKey = $this->valueKeyPrefix . $key;
        $this->_delete($valueKey);
    }
    
    private function deleteValueMetaData($key)
    {
        $metaKey  = $this->metaKeyPrefix  . $key;
        $this->_delete($metaKey);
    }
    
    private function deleteTagMetaData($tag)
    {
        $tagKey = $this->tagKeyPrefix . $tag;
        $this->_delete($tagKey);
    }
    
    private function removeFromTagMetaData($key, $tag, $expire = null)
    {
        $tagKey = $this->tagKeyPrefix . $tag;
        $tagMetaData = $this->getTagMetaData($tag);
        if(in_array($key, $tagMetaData['keys'])) {
            unset($tagMetaData['keys'][$key]);
            $this->_set($tagKey, $tagMetaData, $expire);
        }
    }
    
    
    
    
    
    
    private function _set($key, $value, $expire = null)
    {
        if(false !== $this->cache->replace($key, $value, null, $expire)) {
            return true;
        }
        
        return $this->cache->set($key, $value, null, $expire);
    }
    
    private function _get($key, $default = null)
    {
        return (false !== ($value = $this->cache->get($key))) ? $value : $default;
    }
    
    private function _delete($key)
    {
        return $this->cache->delete($key);
    }
    
    private function __construct(array $options = array())
    {
        if(!class_exists('Memcache')) {
            throw Exception('Memcache module is not available.');
        }
        
        $this->cache = new Memcache();
        
        $this->lifetime  = isset($options['lifetime'])  ? $options['lifetime']  : self::$defaultLifetime;
        $this->namespace = isset($options['namespace']) ? $options['namespace'] : md5(dirname(__FILE__));
        
        $this->valueKeyPrefix = $this->namespace . self::SEPARATOR . self::KEY_INDICATOR_VALUE . self::SEPARATOR;
        $this->metaKeyPrefix  = $this->namespace . self::SEPARATOR . self::KEY_INDICATOR_META  . self::SEPARATOR;
        $this->tagKeyPrefix   = $this->namespace . self::SEPARATOR . self::KEY_INDICATOR_TAG   . self::SEPARATOR;
        
        $servers = isset($options['servers'])
            ? $options['servers']
            : array(
                  array(
                      'host'       => 'localhost',
                      'port'       => 11211,
                      'persistent' => true
                  )
              );
        
        foreach($servers as $server)
        {
            $host       = isset($server['host'])       ? $server['host']       : 'localhost';
            $port       = isset($server['port'])       ? $server['port']       : 11211;
            $persistent = isset($server['persistent']) ? $server['persistent'] : true;
            $weight     = isset($server['weight'])     ? $server['weight']     : 1;
            
            if(!$this->cache->addServer($host, $port, $persistent, $weight)) {
                throw Exception(sprintf("Can't connect to Memcache Server (%s:%s)", $host, $port));
            }
        }
        
        
    }
        
}