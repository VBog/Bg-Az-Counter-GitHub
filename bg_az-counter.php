<?php
/* 
    Plugin Name: Bg Az-Counter 
    Plugin URI: https://bogaiskov.ru
    Description: Подсчет количества посещений страниц на базе stat.azbyka.ru
    Version: 2.7
    Author: VBog
    Author URI: https://bogaiskov.ru 
	License:     GPL2
	GitHub Plugin URI: https://github.com/VBog/Bg-Az-Counter-GitHub/
*/

/*  Copyright 2019  Vadim Bogaiskov  (email: vadim.bogaiskov@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*****************************************************************************************
	Блок загрузки плагина 
	powered by Redis-based server for collecting view statistics
	https://gitlab.eterfund.ru/azbyka/stats-server
	
******************************************************************************************/

// Запрет прямого запуска скрипта
if ( !defined('ABSPATH') ) {
	die( 'Sorry, you are not allowed to access this page directly.' ); 
}
define('BG_COUNTER_VERSION', '2.7');

define('BG_COUNTER_LOG', dirname(__FILE__ ).'/bg_counter.log');
define('BG_COUNTER_STAT_COUNTERS','https://stat.azbyka.ru/counters');
define('BG_COUNTER_STAT_RATING','https://stat.azbyka.ru/rating');

define('BG_COUNTER_STAT_RATE','https://stat.azbyka.ru/rate');
define('BG_COUNTER_STAT_SCORE','https://stat.azbyka.ru/item-score');
define('BG_COUNTER_STAT_SCORELIST','https://stat.azbyka.ru/scores-list');

define('BG_COUNTER_STAT_BATCH','https://stat.azbyka.ru/batch-query');

define('BG_COUNTER_STAT_SET','https://stat.azbyka.ru/set-counter');
define('BG_COUNTER_STAT_SET_RATINGS','https://stat.azbyka.ru/set-rating');
define('BG_COUNTER_REALTIME_VIEW','wss://stat.azbyka.ru/realtime-view');
define('BG_COUNTER_REALTIME_UPDATES','wss://stat.azbyka.ru/updates');
$upload_dir = wp_upload_dir();
define('BG_COUNTER_ARCHIVE_COUNTER',str_replace(ABSPATH, '', $upload_dir['basedir']).'/bg_az_counter.json');
define('BG_COUNTER_ARCHIVE_RATING',str_replace(ABSPATH, '', $upload_dir['basedir']).'/bg_az_rating.json');
define('BG_COUNTER_DEFECT',str_replace(ABSPATH, '', $upload_dir['basedir']).'/bg_az_counter_defect.json');
// Определяем имя проекта
if (!isset($project)) {
	if (wp_parse_url( site_url(), PHP_URL_HOST ) == 'azbyka.ru') {
		$project = wp_parse_url( site_url(), PHP_URL_PATH );
		$project = ltrim($project, '/');
		if (!$project) $project = 'main';	// Главный сайт 
		else list($project) = explode ('/', $project);
	} else {
		$project = site_url();
		$project = wp_parse_url( $project, PHP_URL_HOST ).wp_parse_url( $project, PHP_URL_PATH );
		$project = preg_replace('#[\.\/\\\\]#i', '_', $project) ;
	}
	$project = '/project/'.$project;
}
// Таблица стилей для плагина
function bg_counter_enqueue_frontend_styles () {
	wp_enqueue_style( "bg_counter_styles", plugins_url( '/css/styles.css', plugin_basename(__FILE__) ), array() , BG_COUNTER_VERSION  );
}
add_action( 'wp_enqueue_scripts' , 'bg_counter_enqueue_frontend_styles' );

// Настройки плагина
include_once ("inc/options.php");
// Виджеты
include_once ("inc/widgets.php");
// Импорт данных из внешних источников и архива
include_once ("inc/import.php");

// JS скрипт 
function bg_counter_enqueue_frontend_scripts () {
	global $project;
	$option = get_option('bg_counter_options');
	
	$theID = '';
	$type = '';
	if (is_single() || is_page()) {	// Только записи и страницы
		$post = get_post();
		if ($post->post_status == 'publish') {	// Только опубликованные
			$theID = $post->ID;
			$type = 'post';
		}
	} elseif (is_category()) {
		$theID = get_query_var('cat');
		$type = 'category';
	} elseif (is_tag()) {
		$theID = get_query_var('tag_id');
		$type = 'tag';
	} elseif (is_home()) {
		$theID = 1;
		$type = 'index';
	}
	wp_enqueue_script( 'bg_counter_websocket', plugins_url( 'js/reconnecting-websocket.min.js', __FILE__ ), false, BG_COUNTER_VERSION, true );
	wp_enqueue_script( 'bg_counter_rating', plugins_url( 'js/rating.js', __FILE__ ), false, BG_COUNTER_VERSION, true );
	wp_enqueue_script( 'bg_counter_proc', plugins_url( 'js/counter.js', __FILE__ ), false, BG_COUNTER_VERSION, true );
	wp_localize_script( 'bg_counter_proc', 'bg_counter', 
		array( 
			'counterurl' => BG_COUNTER_STAT_COUNTERS, 			// Всегда 'https://stat.azbyka.ru/counters'
			'rateurl' => BG_COUNTER_STAT_RATE, 					// Всегда 'https://stat.azbyka.ru/rate'
			'scoreurl' => BG_COUNTER_STAT_SCORE, 				// Всегда 'https://stat.azbyka.ru/item-score'
			'batch' => BG_COUNTER_STAT_BATCH, 					// Всегда 'https://stat.azbyka.ru/batch-query'
			'websocket' => BG_COUNTER_REALTIME_VIEW, 			// Всегда 'wss://stat.azbyka.ru/realtime-view'
			'updatesocket' => BG_COUNTER_REALTIME_UPDATES, 		// Всегда 'wss://stat.azbyka.ru/updates'
			'updatetime' => (int) $option['update'], 			// Время обновление счетчиков онлайн-посетителей
			'maxreconnect' => (int) $option['maxreconnect'], 	// Макс. количество повторов подключений
			'project' => $project,								// Имя текущего проекта, например, '/propovedi'
			'type' => $type,									// Тип объекта 'post', 'category', 'tag', 'index' или пусто
			'ID' => $theID,										// ID объекта 
			'votes5' => 'голосов',
			'votes2' => 'голоcа', 
			'vote1' => 'голос',
			'voted' => 'Вы уже проголосовали',
			'price' => array("худо", "слабо", "сносно", "достойно", "чудесно"),
			'debug' => ((int) $option['debug'])?true:false		// Выводить или нет инфу в консоль
		)
	);
}	 
if ( !is_admin() ) {
	add_action( 'wp_enqueue_scripts' , 'bg_counter_enqueue_frontend_scripts' ); 
}

// Регистрируем крючок на удаление плагина
if (function_exists('register_uninstall_hook')) {
	register_uninstall_hook(__FILE__, 'bg_counter_deinstall');
}
function bg_counter_deinstall() {
	delete_option('bg_counter_options');
	delete_option('bg_pvc_loaded');
	delete_option('bg_wppp_loaded');
	delete_option('bg_wppm_loaded');
	delete_option('bg_bgar_loaded');
	delete_option('bg_archive_status');
	delete_option('bg_counter_period');
}

// Запускаем счетчик посещений
include_once ("inc/counter.php");
// Запускаем голосование
include_once ("inc/rating.php");
