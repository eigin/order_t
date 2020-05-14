<?php
/**
 *	API script
 *  @author Eigin <sergei@eigin.net>
 *	@version 2.0
 */

session_start();

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// тестовые данные
require_once 'test_data.php';
$test_num=14;
$class 	= $nom_test[$test_num]['class'];
$action = $nom_test[$test_num]['action'];
$param  = $nom_test[$test_num]['param'];

// получим данные с фронта в формате API
// $model = 'models\\' .$_POST['model'];
// $action = $_POST['action'];
// $param = $_POST['param'];

// установим автозагрузку классов
spl_autoload_register ( function ($className) {
    $path = __DIR__.'/'.str_replace('\\', '/', $className).'.php';
    require $path;
});

// запустим счетчик выполнения
$start = microtime(true);

// выберем одну из рабочих директорий для входа, в зависимости от фронт-запроса
$app_path = file_exists(__DIR__ .'/control/'.$class.'.php') ? 'control\\' : 'model\\';
$class = $app_path.$class;

// выполним запрос по API
// укажем класс, действие и параметры 
$res = (new $class)->$action($param);

// посчитаем время выполнения задачи
$finish = microtime(true);
$delta = $finish - $start;

// результат выполнения
echo '<pre>';
print_r($res);

// время выполнения
echo '</pre>';
echo '<pre>time left = '.round($delta,6).' сек.';
