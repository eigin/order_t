<?php
	
	// Получить продукт по id
	$nom_test[0]  = [
		'class' => 'Prod',
		'action' => 'getByField',
		'param' =>  [
						'id_prod'=>5
					]
	];

	// Добавить продукт
	$nom_test[1]  = [
		'class' => 'Prod',
		'action' => 'add',
		'param' =>  [
			 			'id_prod_type'=>1,
			 			'id_ed'=>1,
			 			'name_prod'=>'Поваренная соль'
			 	    ]
	];

	// Изменить продукт
	$nom_test[2]  = [
		'class' => 'Prod',
		'action' => 'edit',
		'param' =>  [
						'id_prod'=>12,
						'name_prod'=>'Приправа для свинины'
				    ]
	];

	// Удалить продукт
	$nom_test[3]  = [

		'class' => 'Prod',
		'action' => 'del',
		'param' => ['id_prod'=>12]
	];

	// Авторизовать пользователя
	$nom_test[4]  = [
		'class' => 'Users',
		'action' => 'login',
		'param' => ['login'=>'admin', 'password'=>'admin']
	];

	// Добавить пользователя
	$nom_test[5]  = [
		'class' => 'Users',
		'action' => 'add',
		'param' => ['login'=>'FFF', 'name_user'=>'Sergei']
	];

	// Прислать новый пароль пользователю
	$nom_test[6]  = [
		'class' => 'Users',
		'action' => 'restorePassword',
		'param' => ['login'=>'madmed@ya.ru']

	];

	// Удалить пользователя
	$nom_test[7]  = [
		'class' => 'Users',
		'action' => 'del',
		'param' => ['id_user'=>18]
	];	

	// Изменить данные пользователя
	$nom_test[8]  = [
		'class' => 'Users',
		'action' => 'edit',
		'param' => ['id_user'=>19, 'name_user'=>'Serg M', 'id_point'=>2 ]
	];	

	// Получить баланс по продукту:
	// кол.остатка, сумма остатка, себестоимость.
	// По всем складам
	$nom_test[9]  = [
		'class' => 'Reg',
		'action' => 'getBalance',
		'param' => ['id_prod'=>2, 'id_stor'=>3]
	];

	// Получить позиции актуальной ТК продукта
	$nom_test[10]  = [
		'class' => 'TCardPos',
		'action' => 'getTCardPos',
		'param' => ['id_prod'=>7, 'actual_date'=>'2020-03-09', 'tree'=>true ]
	];

	// Получить актуальные ТК, в которых участвует продукт
	$nom_test[11]  = [
		'class' => 'Prod',
		'action' => 'getPartInTechCard',
		'param' => ['id_prod'=>13, 'actual_date'=>'2020-03-19']
	];

	// Получить позиции продуктов в документе движения
	$nom_test[12]  = [
		'class' => 'MovPos',
		'action' => 'getMovPos',
		'param' => ['id_mov'=>1 ]
	];

	// Провести документ
	$nom_test[13]  = [
		'class' => 'AccMov',
		'action' => 'accMov',
		'param' => ['id_mov'=>5]
	];

	// Провести группу документов
	$nom_test[14]  = [
		'class' => 'AccMov',
		'action' => 'accMovGroup',
		'param' => []
	];	

	// Удалить документ
	$nom_test[15]  = [
		'class' => 'Mov',
		'action' => 'delMov',
		'param' => ['id_mov'=>10]
	];

	// Копировать документ
	$nom_test[16]  = [
		'class' => 'Mov',
		'action' => 'copyMov',
		'param' => ['id_mov'=>2]
	];
