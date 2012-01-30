<?php
/*
    Интерфейс кэширования в файлах на основе интерфейса ICacheService
*/
class FileCacheService extends BaseCacheService
{
    public function __construct($options)
    {
        $this->path = $options['path'];
        $this->lifetime = $options['lifetime'];
        $this->pageID = $options['pageID'];
        $this->configuration = ConfigService::getInstance();
    }
    
    /*
    Положить значение в кэш
    */
    public function set($key, $value)
    {
        return file_put_contents($this->path.'/'.$key , $value);
    }

    /*
    Получить значение из кэша
    */
    public function get($key)
    {
        $fp = $this->path.'/'.$key;
        if (file_exists($fp) && (time()-filemtime($fp))<$this->lifetime)
        {
            return file_get_contents($fp);
        }
        else
        {
            if (file_exists($fp)) unlink($fp);
            return;
        }    
    }
}
?>
