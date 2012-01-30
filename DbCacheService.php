<?php
/*
    Интерфейс кэширования в БД на основе интерфейса ICacheService
*/
class DbCacheService extends BaseCacheService
{
    public function __construct($options)
    {
        $this->db = ($options['db']) ? $options['db'] : Database::getInstance();
        $this->configuration = ConfigService::getInstance();
        $this->lifetime = $options['lifetime'];
        $this->pageID = $options['pageID'];
        $this->table = ($options['table'])?$options['table']:'orm_cache';
    }
    
    /*
    Положить значение в кэш
    */
    public function set($key, $value)
    {
        $sql = "INSERT INTO `".$this->table."` (`key`, `value`, `ts`) values ('".mysql_real_escape_string($key)."', '".mysql_real_escape_string($value)."', ".time().")";
        return $this->db->query($sql);
    }

    /*
    Получить значение из кэша
    */
    public function get($key)
    {
        $life = time()-$this->lifetime;
        $result = $this->db->query("SELECT * FROM `".$this->table."` WHERE `key` = '".mysql_real_escape_string($key)."' and `ts`>$life ORDER BY `id`");
        $r = mysql_fetch_assoc($result);
        $sql = "DELETE FROM `".$this->table."` WHERE `key` = '".mysql_real_escape_string($key)."' AND `id`<>{$r['id']}";
        if ($r['value']) $this->db->query($sql);
        return $r['value'];
    }
}
?>
