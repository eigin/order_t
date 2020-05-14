<?php
/**
 *  Класс модели списка продуктов в движениях
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.0
 */

namespace model;

use control\SqlBuild;


class MovPos extends Base
{
    /**
     * установить имя таблицы для базовых функций работы с БД
     */
    function __construct ()
    {
        parent::__construct('t_mov_pos');
    }


    /**
     * получить список позиций документа, если они есть
     * @param  int   $id_mov   код документа
     * @return array
     */
    public function isMovPos ($id_mov)
    {
        // получим список позиций в документе
        $str = $this->_sql_string
            ->select('t_mov_pos', ['*'])
            ->where ('id_mov', '=', $id_mov)
            ->getSQL();
        return $this->__getResult($str);
    }


    /**
     * Находит список позиций документа.
     * Определяет, есть ли в списке комплекты и подтягивает их актуальные ТК.
     * @param  array $param      ['id_mov'=>'значение']    код движения
     */
    public function getMovPos (array $param)
    {
        // получим основные данные по движению
        $str = $this->_sql_string
            ->select('t_mov', ['date_mov, id_mov_type, id_stor, id_stor_to'])
            ->where ('id_mov', '=', $param['id_mov'])
            ->getSQL();
        $main = $this->__getResult($str);

        // под-запрос: получить список дат актуальных ТК для продукта
        $str_in = $this->_sql_string
            ->select('t_tc', ['id_prod, MAX(date_start) as date_start'])
            ->where ('date_start', '<=', $main[0]['date_mov'])
            ->group (['id_prod'])
            ->getSQL();

        // основной запрос: получим список комплектов
        $str = $this->_sql_string
            ->select('t_tc', ['t_tc.id_prod, 1 as kit, as_prod, qty_mov,
                                 amn_mov, mdf, name_ed, name_prod'])
            ->join  ('', 't_tc', 't_tc_pos', 'id_tc')                       // выберем ТК у которых есть список позиций (ненулевые ТК)
            ->join  ('','t_tc', 't_prod', 'id_prod')                        // выберем информацию по продуктам
            ->join  ('','t_prod', 't_ed', 'id_ed')                          // выберем информацию по ед.изм
            ->join  ('join ('.$str_in.')', 't_tc', 't_tmp', 'id_prod')      // подключим подзапрос и получим актуальные ТК
            ->join  ('','t_tmp', 't_mov_pos', 'id_prod')                    // выберем из списка позиций документа, только позиции с ненулевыми ТК
            ->where ('t_tc.date_start','=','/t_tmp.date_start')             // будем брать ненулевые ТК только с актуальной датой
            ->where ('id_mov','=', $param['id_mov'])                        // код движения (входной параметр)
            ->group (['id_mov_pos'])                                        // группируем совпадения при пересечении данных таблиц
            ->getSQL();
        $data = $this->__getResult($str);

        // составим список id_prod для исключения
        $not_in = [];
        foreach ($data as $key => $value) $not_in[]=$value['id_prod'];
        $not_in = implode(',', $not_in);

        // получим список продуктов (исключим комплекты)
        $str = $this->_sql_string
            ->select('t_mov_pos', ['t_mov_pos.id_prod, 0 as kit, qty_mov, amn_mov, mdf,
                                           name_ed, name_prod'])
            ->join  ('','t_mov_pos', 't_prod', 'id_prod')
            ->join  ('','t_prod', 't_ed', 'id_ed')
            ->where ('id_mov','=', $param['id_mov'])
            ->where ('t_mov_pos.id_prod','NOT IN', $not_in)
            ->getSQL();
        $data2= $this->__getResult($str);

        // объединим список комплектов и продуктов
        $data = array_merge($data,$data2);

        return $data;

    }


}
