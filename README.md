# MemcacheTagging

MemcacheTagging is a Memcached client with tagging features.

## Requirements

* PHP 5 with Memcache module
* Memcache module >= 2.0 for multi server support (optional)

## Get Started

    require dirname(dirname(__FILE__)) . '/path/to/MemcacheTagging.class.php';
    
    // create a instance.
    $cache = MemcacheTagging::getInstance();
    
    // set a value with tags.
    $cache->set('key', 'value', array('tag_foo', 'tag_bar'));
    
    // retrieve with a tag.
    $values = $cache->getByTag('tag_foo');
    
    // delete with a tag.
    $cache->deleteByTag('tag_foo');

## Options

Options for the factory method are below, and all optional.  

* lifetime
* namespace
* servers

The "servers" option is expected to be a two-dimensional array like below.

    $options = array(
        'servers' => array(
            array(
                'host' => 'localhost',
                'port' => 11211,
            ),
            array(
                'host' => 'localhost',
                'port' => 11212,
            ),
        ),
    );

# Notes

* It's not atomic.
* As your own risk.