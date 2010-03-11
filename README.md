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

# License

Copyright (c) 2010 Masato Hirai

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
