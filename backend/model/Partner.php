<?php
/**
 *  Класс модели для контрагентов
 *  @author Eigin <sergei@eigin.net>
 *  @version 1.0
 */

namespace model;

use control\SqlBuild;


class Partner extends Base
{
    /**
     * установить имя таблицы для базовых функций работы с БД
     */
    function __construct ()
    {
        parent::__construct('t_partner');
    }


}
