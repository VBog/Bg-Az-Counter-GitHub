<?php
/* 
    Plugin Name: Bg Az-Counter 
    Plugin URI: https://bogaiskov.ru
    Description: Подсчет количества посещений страниц на базе stat.azbyka.ru
    Version: 2.10.8
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
define('BG_COUNTER_VERSION', '2.10.8');

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
//		else list($project) = explode ('/', $project);
		else $project = str_replace ('/', '_', $project);
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
	$countviews = 'on';
	if (is_single() || is_page()) {	// Только записи и страницы
		$post = get_post();
		if ($post->post_status == 'publish') {	// Только опубликованные
			$theID = $post->ID;
			$type = 'post';
			$countviews = get_post_meta($theID, 'отключить_счетчик',true)?'':'on';
		}
		else $countviews = '';
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
			'countviews' => $countviews,
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

/*****************************************************************************************

	Блок фальсификации данных статистики
	
******************************************************************************************/
add_action('admin_init', 'azbyka_falsification', 1);
// Создание блока в админке
function azbyka_falsification() {
	$post_types = get_post_types( [ 'publicly_queryable'=>1 ] );
	$post_types['page'] = 'page';       // встроенный тип не имеет publicly_queryable
	unset( $post_types['attachment'] ); // удалим attachment
    add_meta_box( 'azbyka_falsification', 'Фальсификация статистики', 'azbyka_falsification_box_func', $post_types, 'side', 'high'  );
}
// Добавление поля 'Фальсификация данных статистики'
function azbyka_falsification_box_func( $post ){
    wp_nonce_field( basename( __FILE__ ), 'azbyka_falsification_nonce' );
	$path = '/post/'.$post->ID;
	$count = getCount($path);
	if ($count)	$count = $count->total; 
	else $count = "Нет счетчика";
?>
    <b>Количество посещений:</b> <?php echo $count; ?><br>
	<label>Введите любое число ≥ 0:<br>
		<input type="number" name="azbyka_falsh_counts" value="" min=0 /><br>и нажмите кнопку "Опубликовать/Обновить".
	</label>
	<i>Если оставить поле пустым, счетчик сохранит свое истинное значение.</i><br>
    <label><input type="checkbox" name="bg_az_counter_not_counting"<?php echo (get_post_meta($post->ID, 'отключить_счетчик',true)?' checked="checked"':'');?> /> Отключить счетчик посещений</label><br>
    <label><input type="checkbox" name="bg_az_counter_not_rating"<?php echo (get_post_meta($post->ID, 'не_отображать_рейтинг',true)?' checked="checked"':'');?> /> Не отображать рейтинг</label>
<?php
}
// Сохранение значений счетчика при автосохранении поста
add_action('save_post', 'azbyka_falsification_update', 0);

// Сохранение значений счетчика при сохранении поста
function azbyka_falsification_update( $post_id ){

    // проверяем, пришёл ли запрос со страницы с метабоксом
    if ( !isset( $_POST['azbyka_falsification_nonce'] )
    || !wp_verify_nonce( $_POST['azbyka_falsification_nonce'], basename( __FILE__ ) ) ) return $post_id;
    // проверяем, является ли запрос автосохранением
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
    // проверяем, права пользователя, может ли он редактировать записи
    if ( !current_user_can( 'edit_post', $post_id ) ) return $post_id;

     if ( isset( $_POST['azbyka_falsh_counts'] ) && $_POST['azbyka_falsh_counts'] != '') {
		$counter = (int) $_POST['azbyka_falsh_counts'];
		$path = '/post/'.$post_id;
		if ($counter >= 0) setCount ($path, $counter);
	 }
	update_post_meta($post_id, 'отключить_счетчик', $_POST['bg_az_counter_not_counting']);
	update_post_meta($post_id, 'не_отображать_рейтинг', $_POST['bg_az_counter_not_rating']);
}

