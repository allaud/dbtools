<?php
/*
    Интерфейс кэширования
*/
interface ICacheService
{
    public function getKey($name);
    public function set($key, $value);
    public function get($key);
}
?>
