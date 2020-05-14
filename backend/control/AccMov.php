<?php
/**
 *  Класс учета движения (проводки)
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.0
 */

namespace control;

use model\{Mov, MovPos, Reg, TCardPos};


class AccMov extends Acc
{   
    /**
     * тест групповой проводки
     */
    public function accMovGroup ()
    {       
        // $this->accMov(['id_mov'=>4]);
        $this->accMov(['id_mov'=>11]);
    }


    /**
     * Провести документ
     * @param  array  $param   ['id_mov'=>значение ]     код движения, если с МИНУСОМ - отменить проводку
     */
    public function accMov ($param)
    {
        $id_mov = $param['id_mov'];

        // проверка на существование документа
        if(!(new Mov)->isMov(abs($id_mov))) exit ('Такого документа не существует');

        // если это отмена проводки
        if($id_mov<0){
            return $this->__delMov(abs($id_mov));
        }  
        // если документ проведен - выходим
        if ((new Reg)->isAcc($id_mov)) 
            exit ('Документ уже проведен');

        // получим основные данные и список позиций движения
        $this->mov   = (new Mov)->getByField($param);
        $arr_mov_pos = (new MovPos)->getMovPos($param);

        // если документ пуст - выходим
        if(!$arr_mov_pos)
            exit ('В документе нет ни одной позиции');

        // исключим повторения - сгруппируем схожие позиции и суммируем кол-во
        $arr_mov_pos = $this->__groupByIdProd($arr_mov_pos);

        // проведем документ, в зависимости от типа движения
        switch ($this->mov[0]['id_mov_type']) {

            // Оприходование
             case '1':

                // если есть более поздние проводки - выходим
                if ($this->__checkProdLastAcc($arr_mov_pos))
                    exit ('Есть более поздние проводки');
                
                // запишем все позиции в регистр
                $this->__addReg($arr_mov_pos, true);

            break;

            // Касса нал, Касса б/нал, ПКО, Списание
             case '2':
             case '3':
             case '4':
             case '5':

                // обработаем список позиций и получим массив для записи в регистр
                $this->__calcMovPos($arr_mov_pos, true);

                // исключим повторения - сгруппируем схожие позиции и суммируем qty
                $this->arr_prod = $this->__groupByIdProd($this->arr_prod);

                // если есть более поздние проводки - выходим
                if ($this->__checkProdLastAcc($this->arr_prod))
                    exit ('Есть более поздние проводки');

                // запишем все позиции в регистр как списание
                $this->__addReg($this->arr_prod, false);

             break;
            
            // Производство
             case '6':

                // обработаем список позиций, не списывая остатки для производимых комплектов
                // т.е. каждую позицию раскладываем полностью, независимо от наличия остатков
                $this->__calcMovPos($arr_mov_pos, false);

                // исключим повторения - сгруппируем схожие позиции и суммируем qty
                $this->arr_prod = $this->__groupByIdProd($this->arr_prod);

                // если есть более поздние проводки - выходим
                if ($this->__checkProdLastAcc($this->arr_prod))
                    exit ('Есть более поздние проводки');

                // обработаем итоговый массив, если продуктов хватает для списания - спишем
                // если нет - выйдем из switch
                if($res = $this->__addReg($this->arr_prod, false)) break;


                // тут неправильно: при производстве нужно расчитать себест-ть из себест-ти продуктов,
                // входящих в комплект! И использовать не addReg, а написать др. функцию... 


                // добавим все комплекты в регистр не раскладывая, т.к. они уже "произведены"
                $this->__addReg($arr_mov_pos, true);

                break;
            
            // Перемещение
             case '7':
                break;
             
            // Инвентаризация НЗП
             case '8':
                break;
            
            // Инвентаризация
             case '9':
                break;

             default:
                 break;
         }

        // если чего-то не хватает - выведем массив продуктов с кол-вом недостачи
        if($this->arr_left) {
            echo '<pre>';
            echo print_r($this->arr_left);
            exit ('Не хватает товара для проводки документа #'.$this->arr_left[0]['id_mov']);
        } else { 
            echo 'Документ успешно проведён';
        }


    }



}
