<?php
/*
    Базовый класс-реализатор кэширования с общим для всех кэшеров функционалом
*/
abstract class BaseCacheService implements ICacheService
{
    /*
    Алгоритм получения уникального ключа
    @param $name имя ключа
    */
    public function getKey($name)
    {
        return $this->pageID.'_'.$this->configuration->shopID.'_'.$name;
    }
}
?>
