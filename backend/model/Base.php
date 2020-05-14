<?php
/**
 *  Базовый класс ДБ
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.0
 *
 *  Соединение с БД и основные действия с записями таблиц
 *  add / edit / del / getByField
 *
 *
 *  Keep the mySql table creating rules:
 *  - prefix table name:         "t_"   name must be like primary key field name (ex: t_name, id_name)
 *  - prefix primary key fields: "id_"  must be first field of the table (PRI)
 *  - prefix joined fields:      "id_"  must be indexed field (MUL)
 * 
 */

namespace model;


use control\SqlBuild;
use config\Config;

 
class Base
{
    protected static $_db = null;
    protected $_table_name;
    protected $_table_columns;
    protected $_key_field;
    protected $_sql_string;


    /**
     * соединиться с БД, подготовить построитель запросов,
     * получить структуру указанной в аргументе таблицы,
     * взять название ключевого поля
     */
    function __construct (string $table_name)
    {
        $this->__ConnectDb();
        $this->_table_name    = $table_name;
        $this->_sql_string    = new SqlBuild;
        $this->_table_columns = $this->__getColumnFrom($this->_table_name);
        $this->_key_field     = $this->_table_columns[0]['Field'];
    }


    /**
     * соединиться с БД mySql, данные для подключения взять из конфига
     */    
    protected function __ConnectDb ()
    {
        if (self::$_db) return self::$_db; // уже подключено
        $db = Config::$db;
        self::$_db = new \mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
        if (self::$_db->error) die ('Connect Error '.self::$_db->connect_error);
        return self::$_db->set_charset('UTF8');
    }
   

    /**
     * добавить запись в текущую таблицу
     */
    public function add (array $param)
    {
        $str = $this->_sql_string
            ->insert ($this->_table_name, $param)
            ->getSQL ();
        self::$_db->query($str) or die(mysqli_error(self::$_db));       
        return  self::$_db->insert_id;
    }


    /**
     * обновить данные по первичному ключу
     */
    public function edit (array $param)
    {
        $str = $this->_sql_string
            ->update ($this->_table_name, $param)
            ->where  ($this->_key_field, '=', $param[$this->_key_field])
            ->getSQL ();       
        self::$_db->query($str) or die(mysqli_error(self::$_db));      
        return true;
    }


    /**
     * удалить данные по первичному ключу
     */
    public function del (array $param)
    {
        $str = $this->_sql_string
            ->delete ($this->_table_name)
            ->where  ($this->_key_field, '=', $param[$this->_key_field])
            ->getSQL ();
        self::$_db->query($str) or die(mysqli_error(self::$_db));
        return true;
    }

    
    /**
     * получить данные из основной таблицы по одному полю,
     * включая данные из всех связанных таблиц
     */
    public function getByField (array $param)
    {
        $field_name = '';
        if($param) $field_name=array_keys($param)[0];
        $this->_sql_string
            ->select($this->_table_name, ['*'])
            ->where ($this->_table_name . '.' . $field_name, '=', $param[$field_name] ?? '');
        $this->__getJoin($this->_table_columns);
        $str = $this->_sql_string
            ->getSQL();
        return $this->__getResult($str);
    }


    /**
     * сформировать строку подключения всех связанных таблиц,
     * если поле индексировано и с префиксом 'id_' - добавить в join
     */
    protected function __getJoin (array $columns)
    {
        foreach ($columns as $key => $value) {        
            if ($value['Key']=='MUL' && substr($value['Field'], 0, 3)=='id_') {    
                $join_table = str_replace('id_','t_', $value['Field']);
                $this->_sql_string
                    ->join  ('', $this->_table_name, $join_table, $value['Field']);
            }
        }
    }


    /**
     * получить структуру таблицы по названию
     */
    private function __getColumnFrom (string $table)
    {
        $str = $this->_sql_string
            ->show_columns_from($table)
            ->getSQL();
        return $this->__getResult($str);
    }


    /**
     * получить по сформированному запросу данные в виде ассоциативного массива
     */
    protected function __getResult (string $str)
    {   
        $array_data = [];
        $results = self::$_db->query($str) or die(mysqli_error(self::$_db));       
        while ($data = $results->fetch_assoc()) $array_data[] = $data;
        return $array_data;
    }



}
