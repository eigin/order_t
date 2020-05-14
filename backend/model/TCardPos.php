<?php
/**
 *  Класс модели списка продуктов в технологических картах
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.0
 */

namespace model;

use control\SqlBuild;


class TCardPos extends Base
{
    /**
     * установить имя таблицы для базовых функций работы с БД
     */
    function __construct ()
    {
        parent::__construct('t_tc_pos');
    }


    /**
     * получить позиции актуальной ТК для комплекта 
     * 
     * @param id_prod     /обязательный
     * @param actual_date /если пустой, устанавливается текущая дата
     */
    public function getTCardPos (array $param)
    {
        $cur_date = date('Y-m-d H:i');
        $actual_date = $param['actual_date'] ?? $cur_date;
        return $this->__getTCardPosTree ($param['id_prod'], $actual_date, $param['tree']);
    }


    /**
     * вернуть дерево массива позиций (разложить комплект в глубину)
     *  если tree = true, - вернуть всё дерево
     */
    private function __getTCardPosTree ($id_prod, $actual_date, $tree)
    {
        // под-под-запрос: получить список дат актуальных ТК для продукта
        $str_in_in = $this->_sql_string
            ->select('t_tc', ['id_prod, MAX(date_start) as date_start'])
            ->where ('date_start', '<=', $actual_date ?? '')
            ->group (['id_prod'])
            ->getSQL();
        // под-запрос: получим коды комплектов для актуальных ТК
        $str_in = $this->_sql_string
            ->select('t_tc', ['t_tc.id_prod'])
            ->join  ('','t_tc', 't_tc_pos', 'id_tc')
            ->join  ('join ('.$str_in_in.')', 't_tc', 't_tmp', 'id_prod')
            ->where ('t_tc.date_start','=','/t_tmp.date_start')
            ->group (['t_tc.id_tc'])            
            ->getSQL();
        // под-запрос2: получить список дат актуальных ТК для продукта
        $str_in2 = $this->_sql_string
            ->select('t_tc', ['id_prod, MAX(date_start) as date_start'])
            ->where ('id_prod', '=', $id_prod)
            ->where ('date_start', '<=', $actual_date ?? '')
            ->getSQL();
        // запрос: получим коды комплектов для актуальной ТК конкретного продукта
        $str = $this->_sql_string
            ->select('t_tc_pos', ['t_tc_pos.id_prod, t_tc_pos.id_tc, 1 as kit, as_prod, qty_tc, name_prod, qty_prod, name_ed'])
            ->join  ('','t_tc_pos', 't_prod', 'id_prod')
            ->join  ('','t_prod', 't_ed', 'id_ed')
            ->join  ('','t_tc_pos', 't_tc', 'id_tc')
            ->join  ('join ('.$str_in2.')', 't_tc', 't_tmp', 'id_prod')
            ->where ('t_tc_pos.id_prod','IN', $str_in)
            ->where ('t_tc.date_start','=','/t_tmp.date_start')
            ->getSQL();
        // отправить составной запрос
        $data = $this->__getResult($str);

        // составим список кодов комплектов для исключения
        $not_in = [];
        foreach ($data as $key => $value) $not_in[]=$value['id_prod'];
        $not_in = implode(',', $not_in);


        // под-под-запрос: получить список дат актуальных ТК для продукта
        $str_in = $this->_sql_string
            ->select('t_tc', ['id_prod, MAX(date_start) as date_start'])
            ->where ('id_prod', '=', $id_prod)
            ->where ('date_start', '<=', $actual_date ?? '')
            ->getSQL();
        // запрос: получим коды продуктов (с исключенными комплектами) для актуальной ТК конкретного продукта
        $str = $this->_sql_string
            ->select('t_tc_pos', ['t_tc_pos.id_prod, t_tc_pos.id_tc, 0 as kit, qty_tc, name_prod, qty_prod, name_ed'])
            ->join  ('','t_tc_pos', 't_prod', 'id_prod')
            ->join  ('','t_prod', 't_ed', 'id_ed')
            ->join  ('','t_tc_pos', 't_tc', 'id_tc')
            ->join  ('join ('.$str_in.')', 't_tc', 't_tmp', 'id_prod')
            ->where ('t_tc.date_start','=','/t_tmp.date_start')
            ->where ('t_tc_pos.id_prod','NOT IN', $not_in)
            ->getSQL();
        // отправить составной запрос
        $data2= $this->__getResult($str);
        $data = array_merge($data,$data2);

        // если разрешено дерево
        if($tree){
            if(empty($data)) return;
            foreach ($data as $key => $value) {
                $data[$key] = $value;
                // если это комплект - рекурсия
                if ($value['kit']) $data[$key]['kit_list'] = $this->__getTCardPosTree ($value['id_prod'], $actual_date, true);
            }
        }
        return $data;
    }


}



