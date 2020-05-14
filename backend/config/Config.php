<?php
/**
 *  Backend configurations Class
 *  @author Eigin <sergei@eigin.net>
 *	@version 2.0
 */

namespace config;

/**
 * Статические данные
 */
class Config
{
	// данные для подключения к БД
	public static $db  = [

		'host' => 'localhost',
		'user' => '***',
		'pass' => '***',
		'name' => '***',

	];

	// сайт администратора для обратной связи
	public static $emailsite = 'sergei@eigin.net';


}
