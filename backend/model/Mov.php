<?php
/**
 *  Класс модели движений продуктов
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.0
 */

namespace model;

use control\SqlBuild;


class Mov extends Base
{
    /**
     * установить имя таблицы для базовых функций работы с БД
     */
    function __construct ()
    {
        parent::__construct('t_mov');
    }

    /**
     * проверка на существование документа
     * @param  int       $id_mov     код документа
     * @return boolean
     */
    public function isMov ($id_mov)
    {
        $str = $this->_sql_string
            ->select($this->_table_name, ['id_mov'])
            ->where ('id_mov', '=', $id_mov)
            ->getSQL();
        $res = self::$_db->query($str);        
        return $res->num_rows ? true : false;        
    }

    /**
     * удаление документа с предварительной проверкой
     * все позиции документа удаляются автоматически
     * @param  array  $param   ['id_mov'=> значение]
     */
    public function delMov ($param)
    {
        $id_mov = $param['id_mov'];

        // проверка на существование документа
        if(!$this->isMov($id_mov)) exit ('Такого документа не существует');

        // вдруг документ проведён
        if((new Reg)->isAcc($id_mov))
            exit ('Нельзя удалить проведённый документ');

        // получим позиции документа
        $mov_pos = new MovPos;
        $arr_mov_pos = $mov_pos->isMovPos($id_mov);

        // если документ не пустой - удалим сначала все позиции
        if($arr_mov_pos)
            foreach ($arr_mov_pos as $value) {
                $mov_pos->del(['id_mov_pos'=>$value['id_mov_pos']]);
            }       

        // теперь удалим сам документ
        $this->del(['id_mov'=>$id_mov]);
            return 'Документ успешно удален';

    }


    /**
     * копия документа
     * @param  array  $param   ['id_mov'=> значение]
     */
    public function copyMov ($param)
    {
        $id_mov = $param['id_mov'];

        // проверка на существование документа
        if(!$this->isMov($id_mov)) exit ('Такого документа не существует');
        
        // получим общую информацию по документу
        $mov = $this->getByField(['id_mov'=>$id_mov]);

        // создадим новый документ с копией основных данных
        $new_id = $this->add([  'id_mov_type' => $mov[0]['id_mov_type'],
                      'id_stor'     => $mov[0]['id_stor'],
                      'id_stor_to'  => $mov[0]['id_stor_to'],
                      'id_user'     => $mov[0]['id_user'],
                      'id_partner'  => $mov[0]['id_partner'],
                      'descr_mov'   => 'Копия '.$mov[0]['descr_mov']
                   ]);

        // получим позиции документа
        $mov_pos = new MovPos;
        $arr_mov_pos = $mov_pos->isMovPos($id_mov);       

        // если есть позиции - создадим их копии для нового документа
        if($arr_mov_pos)
            foreach ($arr_mov_pos as $value) {
                $mov_pos->add([  'id_mov'    => $new_id,
                                 'id_prod'   => $value['id_prod'],
                                 'qty_mov'   => $value['qty_mov'],
                                 'amn_mov'   => $value['amn_mov'],
                                 'mdf'       => $value['mdf'],
                                 'price_out' => $value['price_out']
                              ]);
            }

        return 'Документ удачно скопирован';

    }


}
