<?php
/*
    Интерфейс кэширования в Memcache на основе интерфейса ICacheService
*/
class MemcacheCacheService extends BaseCacheService
{
    public function __construct($options)
    {
        if ($options['memcache']) 
            $this->memcache = $options['memcache'];
        else
        {
            $this->memcache = new Memcache();
            $this->memcache->connect($options['server'], $options['port']);
        }
        $this->lifetime = $options['lifetime'];
        $this->pageID = $options['pageID'];
        $this->configuration = ConfigService::getInstance();
    }
    
    /*
    Положить значение в кэш
    */
    public function set($key, $value)
    {
        $this->memcache->add($key, $value, false, $this->lifetime);
    }

    /*
    Получить значение из кэша
    */
    public function get($key)
    {
        return $this->memcache->get($key);
    }
}
?>
