<?php
/**
 *  Класс построителя запросов для БД mySql
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.2
 */

namespace control;


class SqlBuild
{
    protected $_query;
    protected $_main_table;


    /**
     * reset query
     */
    protected function __reset(): void
    {
        $this->_query = new \stdClass;
    }


    /**
     * select from
     */
    public function select (string $table, array $fields)
    {
        $this->__reset();
        $this->_main_table = $table;
        $this->_query->base = 'SELECT '.implode(', ', $fields).' FROM '.$table;
        return $this;
    }


    /**
     * insert into
     */
    public function insert (string $table, array $fields)
    {
        $this->__reset();
        $set = [];
        foreach ($fields as $key => $value) $set[] = $key.' = "'.$value.'"';
        $this->_query->base = 'INSERT INTO '.$table.' SET '.implode(', ', $set);      
        return $this;
    }


    /**
     * update
     */
    public function update (string $table, array $fields)
    {
        $this->__reset();
        $set = [];
        foreach ($fields as $key => $value) $set[] = $key . ' = "' . $value . '"';
        $this->_query->base = 'UPDATE '.$table.' SET '.implode(', ', $set);       
        return $this;
    }


    /**
     * delete
     */
    public function delete (string $table)
    {
        $this->__reset();
        $this->_query->base = 'DELETE FROM '.$table;
        return $this;
    } 


    /**
     * inner join
     *
     * @param $join       / любой текст вместо JOIN. Бывает нужно всунуть подзапрос, или
     *                      изменить на LEFT/RIGHT JOIN. Если пустой, - JOIN подставится сам.
     * @param $table_to   / имя таблицы, к которой присоединяем
     * @param $table_join / имя таблицы, которую присоединяем
     * @param $field      / имя ключевого поля, по которому связаны таблицы
     */
    public function join (string $join, string $table_to, string $table_join, string $field)
    {
        if(!$join) $join = ' JOIN';
        $this->_query->join[] = $join.' '.$table_join.' ON '.$table_to.'.'.$field.'='.$table_join.'.'.$field;
        return $this;
    }


    /**
     * where
     *
     * @param $field    / имя поля условия
     * @param $operator / оператор условия =, <, >, ON, LIKE, IN и т.п...
     * @param $value    / значение условия
     */
    public function where (string $field, string $operator, $value)
    {   
        if(!$value) return $this; 
        if($operator=='IN' || $operator=='NOT IN'){
            $this->_query->where[] = $field.' '.$operator.' ('.$value.')';
        } else {
            $ap='"';

            if(gettype($value)=='string' && substr($value, 0, 1)=='/') {     // при наличии этого символа убрать кавычки и сам символ у $value
                $ap = '';
                $value = substr($value, 1);
            }
            $this->_query->where[] = $field.$operator.$ap.$value.$ap;
        }
        return $this;
    }
  

    /**
     * order by
     *
     * @param fields  / массив полей для сортировки ['... ,  ... ,  ...']
     * @param direct  / направление сортировки ABS, DESC
     */
    public function order (array $fields, string $direct)
    {
        $this->_query->order = ' ORDER BY '.implode(', ', $fields).' '.$direct;
        return $this;
    }


    /**
     * group by
     */
    public function group (array $fields)
    {
        $this->_query->group = ' GROUP BY '.implode(', ', $fields);
        return $this;
    }


    /**
     * limit
     */
    public function limit (string $offset, string $range)
    {
        $this->_query->limit = ' LIMIT '.$offset.', '.$range;
        return $this;
    }


    /**
     * get columns from current table
     */
    public function show_columns_from (string $table)
    {
        $this->__reset();
        $this->_query->base = 'SHOW COLUMNS FROM '.$table;
        return $this;
    }


    /**
     * collect query string
     */
    public function getSQL (): string
    {
        $sql = $this->_query;     
        if (!empty($sql->join))  $sql->base .= implode(' ', $sql->join);
        if (!empty($sql->where)) $sql->base .= ' WHERE '.implode(' AND ', $sql->where);
        if (isset($sql->group))  $sql->base .= $sql->group;        
        if (isset($sql->order))  $sql->base .= $sql->order;        
        if (isset($sql->limit))  $sql->base .= $sql->limit;
        return $sql->base;
    }

}
