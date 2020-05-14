<?php
/**
 *  Класс модели технологических карт
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.0
 */

namespace model;

use control\SqlBuild;


class TCard extends Base
{
    /**
     * установить имя таблицы для базовых функций работы с БД
     */
    function __construct ()
    {
        parent::__construct('t_tc');
    }

    /**
     * запросить список актуальных ТК, в которых участвует продукт
     */
    public function getPartInTechCard ($id_prod, $actual_date) 
    {
        // подзапрос: получить все ТК с участием продукта
        $str_in = $this->_sql_string
            ->select('t_tc_pos', ['t_tc_pos.id_tc'])    		// выбираем все id ТК
            ->where ('t_tc_pos.id_prod', '=', $id_prod)         // для указанного продукта
            ->getSQL();
        // основной запрос
        $str = $this->_sql_string
            ->select('t_tc', ['t_tc.id_tc, date_start'])        // выбираем все ТК
            ->where ('t_tc.id_tc', 'IN', $str_in)               // с номерами id из подзапроса
            ->where ('date_start', '<=', $actual_date ?? '')    // исключаем неактуальные
            ->getSQL();
        return $this->__getResult($str);
    } 





}
