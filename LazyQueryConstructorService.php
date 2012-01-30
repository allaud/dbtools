<?php
/*
   Ленивый конструктор SQL-запросов
*/

class LazyQueryConstructorService
{
    protected $query;
    protected $params;
    
    protected $queryTable;
    protected $queryType;
    protected $queryFields;
    protected $querySet;
    protected $queryInsert;
    protected $queryOrderBy;
    protected $queryGroupBy;
    protected $queryLimit;
    
    protected $queryAndWhere = array();
    protected $queryOrWhere = array();
    
    protected $queryLeftJoin = array();
    protected $queryRightJoin = array();
    protected $queryInnerJoin = array();
        
    public function _construct()
    {
        //init
    }

    /*
        конструктор SELECT-запросов
    */
    public function select($fields = '*', $aliasTable = false, $addQuotes = true, $distinct = false)
    {
        //очищаем аттрибуты объекта
        $this->skipQueryData();
        //echo '!!!';
        //устанавливаем тип запроса
        $this->queryType = (!$distinct)?'SELECT':'SELECT DISTINCT';
        $quot = ($addQuotes)?"`":"";
        //устанавливаем поля
        if ($fields) foreach ($fields as $k=>$v)
            $fields[$k] = (!$aliasTable)? "$quot$v$quot" : "$quot$aliasTable$quot.$quot$v$quot as $quot$v$quot";
        if (is_array($fields))
            $this->queryFields = implode(',', $fields);
        else
            $this->queryFields =  $fields;
            
        return $this;
    }

    public function selectDistinct($fields = '*', $aliasTable = false, $addQuotes = true)
    {
        $this->select($fields, $aliasTable, $addQuotes, true);
        //echo $this->queryType;
        return $this;
    }
    
    /*
        конструктор DELETE-запросов
    */
    public function delete()
    {
        $this->queryType = 'DELETE';
        return $this;
    }

    /*
        конструктор UPDATE-запросов
    */
    public function update($table)
    {
        $this->queryType = 'UPDATE';
        $this->queryTable = $table;
        return $this;
    }

    /*
        конструктор INSERT-запросов
    */
    public function insert($table)
    {
        $this->queryType = 'INSERT';
        $this->queryTable = $table;
        return $this;
    }

    /*
        конструктор тела update-запроса
        @param $assoc assoc
    */
    public function set($assoc)
    {
        $safeAssoc = $this->makeSafeParameters($assoc);
        foreach ($safeAssoc as $k=>$v)
            $pipe[] = "`$k`=$v";
        $this->querySet = " SET ".implode(',', $pipe);
        return $this;
    }

    /*
        конструктор тела INSERT-запросов
        @param $assoc assoc
    */
    public function insertBody($assoc)
    {
        $safeAssoc = $this->makeSafeParameters($assoc);
        $keys = array_keys($assoc);
        foreach ($keys as $k=>$v) $keys[$k] = "`$v`";
        foreach ($safeAssoc as $k=>$v) $safeAssoc[$k] = "$v";
        $this->queryInsert = " (".implode(',', $keys).") VALUES (".implode(',', $safeAssoc).")";
        return $this;
    }

    /*
        Конструктор FROM части запроса
        @param $table string
    */
    public function from($table)
    {
        $this->queryTable = $table;
        return $this;
    }

    /*
        Конструктор AND WHERE запроса
        @param $where string
        @param $params array
    */
    public function andWhere($where = '1', $params = array())
    {
        return $this->where($where, $params, 'AND');
    }

    /*
        Конструктор OR WHERE запроса
        @param $where string
        @param $params array
    */
    public function orWhere($where = '1', $params = array())
    {
        return $this->where($where, $params, 'OR');
    }

    /*
        Конструктор WHERE запроса
        @param $where string
        @param $params array
        @param $prefix string
    */
    public function where($where = '1', $params = array(), $prefix = '')
    {
        if (!$prefix || $prefix=='AND')
            $this->queryAndWhere[] = $this->prepare($where, $params);
        else
            $this->queryOrWhere[] = $this->prepare($where, $params);
        return $this;
    }

    /*
        Генератор RIGHT JOIN запроса
        @param $order string
    */
    public function rightJoin($table, $where, $params)
    {
        $this->join($table, $where, $params, 'RIGHT');
        return $this;
    }

    /*
        Генератор INNER JOIN запроса
        @param $order string
    */
    public function innerJoin($table, $where, $params)
    {
        $this->join($table, $where, $params, 'INNER');
        return $this;
    }

    /*
        Генератор JOIN запроса
        @param $order string
    */
    public function join($table, $where, $params = array(), $side = 'LEFT', $useQuotes = true)
    {
        $quot = ($useQuotes)?"`":"";
        $table=$quot.$table.$quot;
        switch ($side)
        {
            case 'INNER':
                $this->queryInnerJoin[$table] = $this->prepare($where, $params);
                break;
            case 'RIGHT':
                $this->queryRightJoin[$table] = $this->prepare($where, $params);
                break;
            case 'LEFT':
            default:
                $this->queryLeftJoin[$table] = $this->prepare($where, $params);
                
        }
        return $this;
    }

    /*
        Генератор ORDER By запроса
        @param $order string
    */
    public function orderBy($order)
    {
        $this->queryOrderBy = $order;
        return $this;
    }

    /*
        Генератор GROUP By запроса
        @param $order string
    */
    public function groupBy($group)
    {
        $this->queryGroupBy = $group;
        return $this;
    }

    /*
        Генератор LIMIT запроса
        @param $count int
        @param $from int
    */
    public function limit($count, $from = 0)
    {
        $this->queryLimit = ($from) ? " $from, $count" : " $count"; 
        return $this;
    }

    /*
        Очищает сгенерированный запрос
        @param $count int
        @param $from int
    */
    public function skipQueryData()
    {
        unset($this->queryTable);
        unset($this->queryType);
        unset($this->queryFields);
        unset($this->querySet);
        unset($this->queryInsert);
        unset($this->queryOrderBy);
        unset($this->queryGroupBy);
        unset($this->queryLimit);
        
        unset($this->queryAndWhere);
        unset($this->queryOrWhere);
        
        unset($this->queryLeftJoin);
        unset($this->queryRightJoin);
        unset($this->queryInnerJoin);
    }

    public function __toString()
    {
        $query = '';
        $where = '';
        $andWhere = implode(' AND ',$this->queryAndWhere);
        $orWhere = implode(' OR ',$this->queryOrWhere);
        if ($andWhere)
            $where.= " WHERE $andWhere";
        if ($orWhere)
            $where.= ($andWhere)? " AND $orWhere":"WHERE $orWhere";
        
        switch ($this->queryType)
        {
            case 'SELECT DISTINCT':
            case 'SELECT':
                $query = "{$this->queryType} {$this->queryFields} FROM {$this->queryTable}";
                if ($this->queryRightJoin) foreach ($this->queryRightJoin as $table=>$on)
                    $query.= " RIGHT JOIN $table ON $on";
                if ($this->queryInnerJoin) foreach ($this->queryInnerJoin as $table=>$on)
                    $query.= " INNER JOIN $table ON $on";
                if ($this->queryLeftJoin) foreach ($this->queryLeftJoin as $table=>$on)
                    $query.= " LEFT JOIN $table ON $on";
                $query.=$where;
                if ($this->queryOrderBy) $query.= " ORDER BY {$this->queryOrderBy}";
                if ($this->queryLimit) $query.= " LIMIT {$this->queryLimit}";
                break;
           case 'UPDATE':
                $query = "{$this->queryType} {$this->queryTable} SET {$this->querySet} $where";
           case 'DELETE':
                $query = "{$this->queryType} FROM {$this->queryTable} $where";
           case 'INSERT':
                $query = "{$this->queryType} INTO {$this->queryTable} {$this->queryInsert}";
        }
        
        return $query;
    }

    /*
        Статический метод перебора параметров
        @param $match string
    */
    public static function walkParam($match)
    {
        global $dbQueryParams;
        return (is_array($dbQueryParams))? array_shift($dbQueryParams) : $dbQueryParams;
    }

    /*
        Подставляет параметры вместо ?
        @param $where string
        @param $params array
    */
    public function prepare($where, $params)
    {
        global $dbQueryParams;
        $dbQueryParams = $this->makeSafeParameters($params);
        $where = preg_replace_callback("#(\?)#i", 'LazyQueryConstructorService::walkParam', $where);
        return $where;
    }

    /*
        функция очистки передаваемых данных
        @param $param mixed
    */
    private function makeSafeParameters($param)
    {
        if (is_array($param))
        {
            foreach($param as $k=>$v) $param[$k] = $this->makeSafeParameters($v);
        }
        else
        {
		    if (!is_numeric($param) && !is_bool($param))
		    {
			    $param = mysql_escape_string($param);
			    $param = "'$param'";
			}
		    if (is_bool($param))
    			$param = ($param) ? 'TRUE' : 'FALSE';

    		return $param;
        }
        return $param;
    }
}
?>
