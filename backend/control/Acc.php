<?php
/**
 *  Класс основных функций учета движения (проводки)
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.0
 */

namespace control;

use model\{Mov, Reg, TCardPos};


class Acc
{   
    protected $arr_left;    // массив продуктов с недостачей
    protected $arr_prod;    // массив продуктов, подготовленных к проводке
    protected $arr_buf;     // буфер для учета остатков комплектов  [ ключ - id_prod => значение - qty ]
    protected $mov;         // общая инфа по движению


    function __construct()
    {
        // обнулим переменные
        $this->mov      = [];
        $this->arr_left = [];
        $this->arr_prod = [];
        $this->arr_buf  = [];
    }


    /**
     * проверка возможности удаления документа
     * @param  int    $id_mov    код движения
     */
    protected function __delMov ($id_mov){

            $reg = new Reg;

            // если документ не проведен - выходим
            if (!$reg->isAcc($id_mov))
            return 'Документ ещё не проведен';

            // получим основную инфу по движению
            $this->mov = (new Mov)->getByField(['id_mov'=>$id_mov]);

            // получим записи по движению из регистра
            $arr_mov_pos_reg = $reg->getMovPosAcc($id_mov);

            // если есть более поздние проводки - выходим
            if ($this->__checkProdLastAcc($arr_mov_pos_reg))
                exit ('Есть более поздние проводки');

            // если это перемещение - проверим также поздние проводки на втором складе
            if($this->mov[0]['id_mov_type']==7){
                $mov[0]['id_stor'] = $mov[0]['id_stor_to'];
                if ($this->__checkProdLastAcc($arr_mov_pos_reg))
                    exit ('Есть более поздние проводки');
            }

            // более поздних проводок нет - удаляем все записи о движении в регистре
            $reg->unAcc($id_mov);
            return 'Проводка успешно отменена';
    }


    /**
     * подготовить позиции движения к проводке
     * @param  array   $arr_mov_pos     массив позиций документа движения
     * @param  boolean  $buff           использовать остатки комплекта на складе
     */
    protected function __calcMovPos ($arr_mov_pos, $buff)
    {      
        // переберем полученные позиции из массива
        foreach ($arr_mov_pos as $data){

            // если это комплект
            if(isset($data['as_prod'])){   $qty_left=$data['qty_mov'];

            // не используются остатки и не установлен параметр 'списывать как продукт' - разложим по частям
                if($data['as_prod']==0 && $buff){
                        // если хватает остатков на складе - добавим в массив списания
                        // если не хватает - спишем то что есть и получим остаток для дальнейшего расчёта
                        $qty_left = $this->__kitBuff($data);
                }

                // если есть нехватка - разложим её по позициям из ТК
                if($qty_left) $this->__calcTCardPos($data['id_prod'], $qty_left);
            }
            // это продукт или комплект с параметром 'списывать как продукт' - сохраним в итоговый массив
            else {               
                $this->arr_prod[] = [  'id_prod'   =>$data['id_prod'],
                                       'kit'       =>0,
                                       'qty_mov'   =>$data['qty_mov'],
                                       'name_ed'   =>$data['name_ed'],
                                       'name_prod' =>$data['name_prod']
                                    ];         
            }

        }
    
    }


    /**
     * Расчет комплекта рекурсивно в глубину.
     * @param  int      $id_prod    код продукта
     * @param  float    $qty_mov    количество движения
     */
    protected function __calcTCardPos ($id_prod, $qty_mov)
    {
        // получим для комплекта список позиций из актуальной ТК, только корень (tree- false)
        $tc_pos = (new TCardPos)->getTCardPos(['id_prod'=>$id_prod, 'actual_date'=>$this->mov[0]['date_mov'], 'tree'=>false]);

        // переберем полученные позиции из массива
        foreach ($tc_pos as $data) {

        // т.к. это элемент ТК, преобразуем его количество для комплекта в кол-во движения
        $data['qty_mov'] = ($data['qty_prod'] / $data['qty_tc']) * $qty_mov;

            // если это комплект
            // и не установлен параметр 'списывать как продукт' - разложим по частям
            if(isset($data['as_prod']) && $data['as_prod']==0) {

                // если хватает остатков на складе - добавим в массив списания
                // если не хватает - спишем то что есть и получим остаток для дальнейшего расчёта
                $qty_left = $this->__kitBuff($data);

                // если есть нехватка - разложим её по позициям из ТК
                if($qty_left) $this->__calcTCardPos($data['id_prod'], $qty_left);

            }
            // это продукт или комплект с параметром 'списывать как продукт' - сохраним в итоговый массив
            else {                              
                $this->arr_prod[] = [  'id_prod'   =>$data['id_prod'],
                                       'kit'       =>0,
                                       'qty_mov'   =>$data['qty_mov'],
                                       'name_ed'   =>$data['name_ed'],
                                       'name_prod' =>$data['name_prod']
                                    ];
            }

        }

    }


    /**
     * Буфер хранит виртуальный массив остатка комплектов, проверяемых во время подготовки к списанию.
     * Если комплект уже проверялся, при запросе остатка учитывается его временное значение из буфера.
     * Т.о. мы подготовим к списанию остатки комплекта, которые были на складе, несмотря на то,
     * что реальный остаток изменится только после проводки документа.
     * Всё, что меньше остатка - на списание, что больше - разложим далее на продукты по актуальной ТК.
     * Код комплекта используем как ключ, остаток на складе - значение.
     * @param  array  $pos  данные записи о текущем продукте
     * @return array        возвращает недостающее количество комплекта
     */
    protected function __kitBuff ($pos)
    {
        $id_prod = $pos['id_prod'];
        $qty_mov = $pos['qty_mov'];

        // есть ли в буфере есть виртуальный остаток этого комплекта
        if(array_key_exists($id_prod, $this->arr_buf)){
            // возьмём тогда его
            $qty = $this->arr_buf[$id_prod];
        } else {
            // если нет - получим остаток и скопируем его в буфер
            $bal = (new Reg)->getBalance($pos['id_prod'], $this->mov[0]['id_stor'], $this->mov[0]['date_mov']);
            $qty = $bal['qty'] ?? 0;
            $this->arr_buf[$id_prod] = $bal['qty'];
        }

        // если остатка хватает
        if($qty_mov <= $qty){

            // получим излишек
            $qty_right = $qty - $qty_mov;

            // добавим в массив для проводки полностью всё кол-во движения
            $this->arr_prod[] = [  'id_prod'    => $id_prod,
                                   'kit'        => 1,
                                   'qty_mov'    => $qty_mov,
                                   'name_ed'    => $pos['name_ed'],
                                   'name_prod'  => $pos['name_prod'],
                                 ];

            // запишем в буфер, сколько комплекта виртуально осталось на складе
            $this->arr_buf[$id_prod] = $qty_right;

            // ничего не возвращаем, комплект раскладывать не нужно, хватило на складе
            // остаток записали в буфер

        } 
        // если остатка не хватает
        else {

            // получим недостающее кол-во
            $qty_left = $qty_mov - $qty;

            // добавим в массив для проводки весь остаток, если он есть
            if($qty)
            $this->arr_prod[] = [  'id_prod'    => $id_prod,
                                   'kit'        => 1,
                                   'qty_mov'    => $qty,
                                   'name_ed'    => $pos['name_ed'],
                                   'name_prod'  => $pos['name_prod']
                                 ];

            // запишем в буфер, что комплекта виртуально не осталось на складе
            $this->arr_buf[$id_prod] = 0;

            // вернём количество комплекта, которое нужно разложить на продукты по ТК
            return $qty_left;
        }

    }


    /**
     * Проверка наличия более поздних проводок у позиций движения
     * @param  array  $mov_pos_data   массив позиций
     * @return bool                 при первом же обнаружении таковых сразу вернёт true
     */
    protected function __checkProdLastAcc ($mov_pos_data)
    {   
        $reg = new Reg;
        foreach ($mov_pos_data as $data) {
            if ($reg->isProdLastAcc($data['id_prod'], $this->mov[0]['id_stor'], $this->mov[0]['date_mov'])) return true;
        }
        return false;
    }


    /**
     * Добавить записи позиций движения в регистр
     * @param  bool  $sign     знак движения: true - плюс
     */
    protected function __addReg ($arr_to_reg, $sign)
    {
        $arr_right = [];
        $reg = new Reg;
        foreach ($arr_to_reg as $data) {
      
            // получим остатки на складе
            $bal = $reg->getBalance($data['id_prod'], $this->mov[0]['id_stor'], $this->mov[0]['date_mov']);
            $qty = $bal['qty'] ?? 0;
            $amn = $bal['amn'] ?? 0;
            $cost_price = $bal['cost_price'] ?? 0;

            $qty_mov = $data['qty_mov'] ?? 0;
            $amn_mov = $data['amn_mov'] ?? 0;

            // изменим остатки в нужную сторону
            if(!$sign){
                // при списании количество движения меняет знак на минус
                // сумма списания = кол-во движения * на себестоимость остатка
                $qty_mov = $data['qty_mov'] * -1;
                $amn_mov = $qty_mov * $cost_price;
            }

            // подводим итоги
            $qty += $qty_mov;
            $amn = $qty_mov ? ($amn_mov / $qty_mov) * $qty : 0;

            // если продукта на складе не достаточно - запишем в соответствующий массив
            if($qty<0) {
                $this->arr_left[] = [   'id_mov'  => $this->mov[0]['id_mov'],
                                        'id_stor' => $this->mov[0]['id_stor'],
                                        'id_prod' => $data['id_prod'],
                                        'kit'     => $data['kit'],
                                        'qty_mov' => $qty_mov,
                                        'amn_mov' => $amn_mov,
                                        'qty'     => $qty,
                                        'amn'     => $amn
                ];
            } else {
                // продукта хватает для списания - в "правильный" массив
                $arr_right[]      = [   'id_mov'  => $this->mov[0]['id_mov'],
                                        'id_stor' => $this->mov[0]['id_stor'],
                                        'id_prod' => $data['id_prod'],
                                        'qty_mov' => $qty_mov,
                                        'amn_mov' => $amn_mov,
                                        'qty'     => $qty,
                                        'amn'     => $amn
                ];
            }
        }

        // если есть недостача - выходим без записи в регистр
        if($this->arr_left) return;

        // недостачи нет - запишем итоги в регистр
        foreach ($arr_right as $data) {

            // если есть что
            if($qty>0)
            $reg->add($data);
        }

    }


    /**
     * Группировка массива и суммирование одного поля
     * @param  array  $arr        / массив для группировки
     * @return array              / итоговый массив без повторяющихся позиций
     */
    protected function __groupByIdProd ($arr)
    {
        $res = [];
        foreach($arr as $v)
        if (!isset($res[$v['id_prod']])) $res[$v['id_prod']] = $v;
        else {
            $res[$v['id_prod']]['qty_mov'] += $v['qty_mov'];
        }
        return $res;
    }


}
