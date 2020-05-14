<?php
/**
 *  Класс ядра приложения
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.0
 */


namespace model;

use control\SqlBuild;



class Reg extends Base
{
    /**
     * установить имя таблицы для базовых функций работы с БД
     */
    function __construct ()
    {
        parent::__construct('t_reg');
    }


    /**
     * получить баланс продукта по складу:
     * количество остатка, сумму остатка,
     * рассчитать себестоимость
     * 
     * @param int     id_prod       код продукта
     * @param int     id_stor       код склада
     * @param string  actual_date   если пустой, устанавливается текущая дата
     */
    public function getBalance ($id_prod, $id_stor, $actual_date)    
    {
        // найдём последний проведённый документ
        $res = $this->getProdLastAcc($id_prod, $id_stor, $actual_date);
        foreach ($res as $data) {
            // если продукт есть, рассчитаем себестоимость
            $cost_price = $data['qty']!=0 ? $data['amn'] / $data['qty'] : 0;
            return [
                    'qty'=>$data['qty'],
                    'amn'=>$data['amn'],
                    'cost_price'=>round($cost_price,2)
                   ];
        }
    }

    /**
     * получить записи из регистра для документа движения
     * @param  int  $id_mov  код движения
     */
    public function getMovPosAcc ($id_mov)
    {
        $str = $this->_sql_string
            ->select($this->_table_name, ['id_mov, id_prod, id_stor'])
            ->where ('id_mov', '=', $id_mov)
            ->group (['id_prod'])
            ->getSQL();
        return $this->__getResult($str);
    }


    /**
     * получить последний проведённый документ с продуктом / на складе / на дату
     * @param  int      $id_prod        код продукта
     * @param  string   $actual_date    дата актуальности
     * @param  int      $id_stor        код склада
     * @return [type]                   
     */
    public function getProdLastAcc ($id_prod, $id_stor, $actual_date)
    {
        $str = $this->_sql_string
            ->select($this->_table_name, ['qty, amn, date_mov, t_reg.id_stor'])
            ->join  ('', $this->_table_name, 't_mov', 'id_mov')
            ->where ('id_prod', '=', $id_prod)
            ->where ('t_reg.id_stor', '=', $id_stor)
            ->where ('date_mov', '<=', $actual_date ?? date('Y-m-d H:i'))
            ->order (['date_mov'],'DESC')
            ->limit (0,1)
            ->getSQL();
        return $this->__getResult($str);
    }


    /**
     * проверить есть ли более поздний проведённый документ с продуктом / на складе / на дату
     * @param  int      $id_prod        код продукта
     * @param  string   $actual_date    дата актуальности
     * @param  int      $id_stor        код склада
     * @return [type]                   
     */
    public function isProdLastAcc ($id_prod, $id_stor, $actual_date)
    {
        $str = $this->_sql_string
            ->select($this->_table_name, ['t_reg.id_stor'])
            ->join  ('', $this->_table_name, 't_mov', 'id_mov')
            ->where ('id_prod', '=', $id_prod)
            ->where ('t_reg.id_stor', '=', $id_stor)
            ->where ('date_mov', '>', $actual_date ?? date('Y-m-d H:i'))
            ->limit (0,1)
            ->getSQL();
        $res = self::$_db->query($str);        
        return $res->num_rows ? true : false;
    }    


    /**
     * Проверка, проведен ли документ
     * @param  int     $id_mov  код движения
     * @return boolean
     */
    public function isAcc ($id_mov)
    {
        $str = $this->_sql_string
            ->select($this->_table_name, ['id_mov'])
            ->where ('id_mov', '=', $id_mov)
            ->limit (0,1)
            ->getSQL();
        $res = self::$_db->query($str);        
        return $res->num_rows ? true : false;
    }
  

    /**
     * удалить данные по коду движения
     * @param  array    $param     ['ключ'=>'значение'] поле для WHERE
     */
    public function unAcc ($id_mov)
    {
        $str = $this->_sql_string
            ->delete ($this->_table_name)
            ->where ('id_mov','=', $id_mov)
            ->getSQL ();
        self::$_db->query($str) or die(mysqli_error(self::$_db));
        return true;
    }


}
