<?php
// Снять комментарий, чтобы сбросить флаг загрузки:
//delete_option('bg_pvc_loaded');		// - " - из Post Views Counter
//delete_option('bg_wppp_loaded');		// - " - из WP Popular Posts
//delete_option('bg_wppm_loaded');		// - " - из произвольных полей "views"
//delete_option('bg_bgar_loaded');		// - " - из плагина Bg Az-Rating



/*****************************************************************************************
	Получить данные из плагина WP Popular Posts
	и отправить на сервер

******************************************************************************************/
function bg_counter_getBgAzRating() {
	global $project;

	ini_set('memory_limit', '1024M');

	// Список типов записей имеющих страницу во форонте
	$post_types = get_post_types( [ 'publicly_queryable'=>1 ] );
	$post_types['page'] = 'page';       // встроенный тип не имеет publicly_queryable
	unset( $post_types['attachment'] ); // удалим attachment

	$posts = get_posts( array(
		'numberposts'	=> -1,
		'post_type'		=> $post_types,
		'post_status'	=>'publish',
		'suppress_filters' => true, // подавление работы фильтров изменения SQL запроса
	) );

// Формируем запрос
	$i = 0;
	$data = array();
	$point = array();
	foreach( $posts as $post ){
		$url = site_url();
		$url = str_replace ( wp_parse_url( $url, PHP_URL_PATH ), '', $url);
		$page = get_permalink( $post );
		$page = str_replace ($url, '', $page);
		$point = bg_counter_get_rating($page);
		if ($point['votes'] > 0) {
			$point['path'] = $project.'/post/'.$post->ID;
			$data[] = $point;
			$i++;
		}
	}
	wp_reset_postdata(); // сброс

	$json = json_encode($data, JSON_UNESCAPED_SLASHES);
	echo $json."<br>";
// Отправить данные на сервер
	if (!setAllRatings ($json)) return false;

	return $i;
}


// Возвращает json массив для текущей страницы
function bg_counter_get_rating($page) {
	global $wpdb;
	
	$page_hash = md5($page);
	$rtmp = $wpdb->get_row("SELECT * FROM _rating WHERE hesh = '".$page_hash."'", ARRAY_A) ;

	if ( $rtmp && $rtmp['rnum'] ) {		// Если запись в БД для данной страницы существует,  обрабатываем данные
		$rating_json['path'] ='';
		$rating_json['votes'] = $rtmp['rnum'];
		$rating_json['ratings'] = $rtmp['rsum'];
	} else {
		$rating_json['path'] ='';
		$rating_json['votes'] = 0;
		$rating_json['ratings'] = 0;
	}
	return $rating_json;
}

/*****************************************************************************************
	Получить данные из произвольного поля views
	и отправить на сервер

******************************************************************************************/
function bg_counter_getWPPostMeta() {
	global $wpdb;
	global $project;

// Получить данные из таблицы
// postid(bigint(20)), day(datetime), last_viewed(datetime), pageviews(bigint(20))
	$old_data = $wpdb->get_results("SELECT post_id,meta_value FROM ".$wpdb->prefix."postmeta WHERE meta_key='views'", ARRAY_A);

// Формируем запрос
	$i = 0;
	$data = array();
	$point = array();
	foreach ($old_data as $row) {
		$point['path'] = $project.'/post/'.$row['post_id'];
		$point['counter'] = (int)$row['meta_value'];
		$data[] = $point;
		$i++;
	}
	$json = json_encode($data, JSON_UNESCAPED_SLASHES);
	echo $json."<br>";
// Отправить данные на сервер
	if (!setAllCounts ($json)) return false;

	return $i;
}
	
/*****************************************************************************************
	Получить данные из плагина WP Popular Posts
	и отправить на сервер

******************************************************************************************/
function bg_counter_getWPPopularPosts() {
	global $wpdb;
	global $project;

// Получить данные из таблицы
// postid(bigint(20)), day(datetime), last_viewed(datetime), pageviews(bigint(20))
	$old_data = $wpdb->get_results("SELECT postid,pageviews FROM ".$wpdb->prefix."popularpostsdata", ARRAY_A);

// Формируем запрос
	$i = 0;
	$data = array();
	$point = array();
	foreach ($old_data as $row) {
		$point['path'] = $project.'/post/'.$row['postid'];
		$point['counter'] = (int)$row['pageviews'];
		$data[] = $point;
		$i++;
	}
	$json = json_encode($data, JSON_UNESCAPED_SLASHES);
	echo $json."<br>";
// Отправить данные на сервер
	if (!setAllCounts ($json)) return false;

	return $i;
}
	
/*****************************************************************************************
	Получить данные из плагина Post Views Counter
	и отправить на сервер

******************************************************************************************/
function bg_counter_getPostViewsCounter() {
	global $wpdb;
	global $project;

// Получить данные из таблицы
// postid(bigint(20)), day(datetime), last_viewed(datetime), pageviews(bigint(20))
	$raw_data = $wpdb->get_results("SELECT id,count FROM ".$wpdb->prefix."post_views", ARRAY_A);
// Суммируем count по id 	
	$old_data = array();
	foreach ($raw_data as $row) {
		if (empty($old_data[$row['id']])) $old_data[$row['id']] = 0;
		$old_data[$row['id']] = (int)$row['count'];
	}
// Формируем запрос
	$i = 0;
	$data = array();
	$point = array();
	foreach ($old_data as $id => $count) {
		$point['path'] = $project.'/post/'.$id;
		$point['counter'] = $count;
		$data[] = $point;
		$i++;
	}
	$json = json_encode($data, JSON_UNESCAPED_SLASHES);
	echo $json."<br>";
// Отправить данные на сервер
	if (!setAllCounts ($json)) return false;

	return $i;
}
/*****************************************************************************************
	Получить данные из файла архива
	и отправить на сервер

******************************************************************************************/
function bg_counter_sendCounterArchiveData() {
	global $project;

// Получить данные из файла архива
	$json = file_get_contents ( ABSPATH.BG_COUNTER_ARCHIVE_COUNTER);
	if (!$json) {
		echo "<br>" ."Ошибка чтения файла: ".BG_COUNTER_ARCHIVE_COUNTER;
		return false;
	}
	$old_data = json_decode($json, true);
// Формируем запрос
	$i = 0;
	$data = array();
	$point = array();
	foreach ($old_data as $row) {
		$point['path'] = $project.'/'.$row['type'].'/'.$row['id'];
		$point['counter'] = $row['value'];
		$data[] = $point;
		$i++;
	}
	$json = json_encode($data, JSON_UNESCAPED_SLASHES);
	echo $json."<br>";
// Отправить данные на сервер
	if (!setAllCounts ($json)) return false;

	return $i;
}

function bg_counter_sendRatingArchiveData() {
	global $project;

// Получить данные из файла архива
	$json = file_get_contents ( ABSPATH.BG_COUNTER_ARCHIVE_RATING);
	if (!$json) {
		echo "<br>" ."Ошибка чтения файла: ".BG_COUNTER_ARCHIVE_RATING;
		return false;
	}
	$old_data = json_decode($json, true);
// Формируем запрос
	$i = 0;
	$data = array();
	$point = array();
	foreach ($old_data as $row) {
		$point['path'] = $project.'/'.$row['type'].'/'.$row['id'];
		$point['votes'] = $row['votes'];
		$point['ratings'] = round ($row['score']*$row['votes']);
		$data[] = $point;
		$i++;
	}
	$json = json_encode($data, JSON_UNESCAPED_SLASHES);
	echo $json."<br>";
// Отправить данные на сервер
	if (!setAllRatings ($json)) return false;

	return $i;
}

	
/*****************************************************************************************
	Получить данные с сервера 
	и сохранить их в файле
******************************************************************************************/

/***** Счетчик посетителей *****/
function bg_counter_saveStatictics() {
	global $project;
	
	ini_set('memory_limit', '1024M');
	
	$limit = 100;
	$offset = 0;
	$count = $limit;
	$data = array();
	
// Получить данные с сервера
	while ($count >= $limit) {
		$result = wp_remote_get (BG_COUNTER_STAT_RATING.$project."?limit=".$limit."&offset=".$offset."&type=post");
		if( is_wp_error( $result ) ) {
			error_log(  PHP_EOL .current_time('mysql')." АРХИВ ПОСЕТИТЕЛЕЙ. Ошибка при получении данных с сервера: ".$result->get_error_message(), 3, BG_COUNTER_LOG );	// сообщение ошибки
			error_log(  " " .$result->get_error_code(), 3, BG_COUNTER_LOG ); 		// ключ ошибки
			return false; 
		}
	
		if (($code = wp_remote_retrieve_response_code( $result )) != 200) {
			error_log(  PHP_EOL .current_time('mysql')." АРХИВ ПОСЕТИТЕЛЕЙ. Сервер вернул код ошибки: ".$code, 3, BG_COUNTER_LOG );	// сообщение ошибки
			error_log(  " " .wp_remote_retrieve_response_message( $result ), 3, BG_COUNTER_LOG ); 		// ключ ошибки
			return false; 
		}

		$json = wp_remote_retrieve_body($result);
		$response = json_decode($json, false);
		if (!$response->success) {
			error_log(  PHP_EOL .current_time('mysql')." АРХИВ ПОСЕТИТЕЛЕЙ. Сервер вернул ответ неудачи:\n".$json, 3, BG_COUNTER_LOG );
			return false;
		}
		$count = count($response->data);
		$i=0;
		if ($count) {
			// Верификация и очистка записей
			foreach ($response->data as &$row) {
				$status = verify_post ($row->id);
				
				if ($status != 'publish') {
					if ($row->value) {
						$defect = " ".$row->id." ".$row->type." ".$row->value." ".$status;
						error_log( PHP_EOL .current_time('mysql')." АРХИВ ПОСЕТИТЕЛЕЙ. Неверный статус записи: ".$defect, 3, BG_COUNTER_LOG );
						// Обнуляем счетчик в ошибочной записи
						$row->value = 0;
						// и отправляем нулевое значение на сервер для удаления
						$path = '/'.$row->type.'/'.$row->id;
						if (!setCount ($path, 0)) {
							error_log( PHP_EOL .current_time('mysql')." АРХИВ ПОСЕТИТЕЛЕЙ. Не удалось обнулить запись: ".$path, 3, BG_COUNTER_LOG );
						} 
					}
				}
				// Удаляем записи с нулевыми значениями из сохраняемого массива
				if ($row->value == 0) unset($response->data[$i]);
				$i++;
			}
			unset($row);
			
			$data = array_merge($data, $response->data);
		}	
			
		$offset += $limit;
	}
	$count = count($data);
	$json = json_encode($data, JSON_UNESCAPED_SLASHES);
	if (!$json)	error_log( PHP_EOL .current_time('mysql')." АРХИВ ПОСЕТИТЕЛЕЙ. Ошибка преобразования в json.", 3, BG_COUNTER_LOG );

	if (file_put_contents ( ABSPATH.BG_COUNTER_ARCHIVE_COUNTER, $json ) === false) {
		error_log( PHP_EOL .current_time('mysql')." АРХИВ ПОСЕТИТЕЛЕЙ. Ошибка записи в файл: ".BG_COUNTER_ARCHIVE_COUNTER, 3, BG_COUNTER_LOG );
		return false;
	}
	
	return ($count);	// Количество записей для данного проекта
}

/***** Результаты голосований *****/
function bg_counter_saveVotes() {
	global $project;
	
	ini_set('memory_limit', '1024M');
	
	$limit = 100;
	$offset = 0;
	$count = $limit;
	$data = array();
	
// Получить данные с сервера
	while ($count >= $limit) {
		$result = wp_remote_get (BG_COUNTER_STAT_SCORELIST.$project."?limit=".$limit."&offset=".$offset."&type=post");
		if( is_wp_error( $result ) ) {
			error_log(  PHP_EOL .current_time('mysql')." АРХИВ ГОЛОСОВАНИЙ. Ошибка при получении данных с сервера: ".$result->get_error_message(), 3, BG_COUNTER_LOG );	// сообщение ошибки
			error_log(  " " .$result->get_error_code(), 3, BG_COUNTER_LOG ); 		// ключ ошибки
			return false; 
		}
	
		if (($code = wp_remote_retrieve_response_code( $result )) != 200) {
			error_log(  PHP_EOL .current_time('mysql')." АРХИВ ГОЛОСОВАНИЙ. Сервер вернул код ошибки: ".$code, 3, BG_COUNTER_LOG );	// сообщение ошибки
			error_log(  " " .wp_remote_retrieve_response_message( $result ), 3, BG_COUNTER_LOG ); 		// ключ ошибки
			return false; 
		}

		$json = wp_remote_retrieve_body($result);
		$response = json_decode($json, false);
		if (!$response->success) {
			error_log(  PHP_EOL .current_time('mysql')." АРХИВ ГОЛОСОВАНИЙ. Сервер вернул ответ неудачи:\n".$json, 3, BG_COUNTER_LOG );
			return false;
		}
		$count = count($response->data);
		$i=0;
		if ($count) {
			// Верификация и очистка записей
			foreach ($response->data as &$row) {
				$status = verify_post ($row->id);
				
				if ($status != 'publish') {
					if ($row->votes) {
						$defect = " ".$row->id." ".$row->type." ".$row->score." ".$row->votes." ".$status;
						error_log( PHP_EOL .current_time('mysql')." АРХИВ ГОЛОСОВАНИЙ. Неверный статус записи: ".$defect, 3, BG_COUNTER_LOG );
						// Обнуляем счетчик в ошибочной записи
						$row->votes = 0;
						$row->score = 0;
						// и отправляем нулевое значение на сервер для удаления
						$path = '/'.$row->type.'/'.$row->id;
						if (!setRating ($path, '{"votes": 0, "ratings": 0}')) {
							error_log( PHP_EOL .current_time('mysql')." АРХИВ ГОЛОСОВАНИЙ. Не удалось обнулить запись: ".$path, 3, BG_COUNTER_LOG );
						} 
					}
				}
				// Удаляем записи с нулевыми значениями из сохраняемого массива
				if ($row->votes == 0) unset($response->data[$i]);
				$i++;
			}
			unset($row);
			
			$data = array_merge($data, $response->data);
		}	
			
		$offset += $limit;
	}
	$count = count($data);
	$json = json_encode($data, JSON_UNESCAPED_SLASHES);
	if (!$json)	error_log( PHP_EOL .current_time('mysql')." АРХИВ ГОЛОСОВАНИЙ. Ошибка преобразования в json.", 3, BG_COUNTER_LOG );

	if (file_put_contents ( ABSPATH.BG_COUNTER_ARCHIVE_RATING, $json ) === false) {
		error_log( PHP_EOL .current_time('mysql')." АРХИВ ГОЛОСОВАНИЙ. Ошибка записи в файл: ".BG_COUNTER_ARCHIVE_RATING, 3, BG_COUNTER_LOG );
		return false;
	}
	
	return ($count);	// Количество записей для данного проекта
}
/*****************************************************************************************
	Проверяет и возвращает статус поста по его ID

******************************************************************************************/
function verify_post ($id) {
	
	$id = (int)$id;
	if ($id <= 0) return 'incorrect_id';

	$post = get_post($id);
	if (!$post) return 'no_post';

	$type = $post->post_type;
	// Список типов записей имеющих страницу во форонте
	$post_types = get_post_types( [ 'publicly_queryable'=>1 ] );
	$post_types['page'] = 'page';       // встроенный тип не имеет publicly_queryable
	unset( $post_types['attachment'] ); // удалим attachment
	if (!in_array($type, $post_types)) return $type;
	$status = $post->post_status;

	return $status;
}

/*****************************************************************************************
	Расписание ежедневной обработки. 
	Начало в полночь текущего часового пояса

******************************************************************************************/
if ( !wp_next_scheduled( 'bg_counter_stack_cron_action' ) ) {
	wp_schedule_event( ceil((current_time('timestamp')-date('Z'))/DAY_IN_SECONDS)*DAY_IN_SECONDS, 'daily', 'bg_counter_stack_cron_action' );
}
add_action( 'bg_counter_stack_cron_action', 'bg_counter_daily_action' );
// Ежедневная обработка
function bg_counter_daily_action () {
	
// Сохраняем архивы результатов
	$result = bg_counter_saveStatictics();											
	if ($result !== false)	$message = "<div class='notice notice-success'><p><b>".	current_time('mysql')."</b> сохранено <b>". $result ."</b> записей в файле архива статистики: <code>". BG_COUNTER_ARCHIVE_COUNTER ."</code></p></div>";
	else $message = "<div class='notice notice-error'><p><b>".current_time('mysql')."</b> Ошибка при сохранении данных статистки. См. журнал ошибок: <code>". BG_COUNTER_LOG ."</code></p></div>";

	$result = bg_counter_saveVotes();
	if ($result !== false)	$message .= "<div class='notice notice-success'><p><b>".current_time('mysql')."</b> сохранено <b>". $result ."</b> записей в файле архива статистики: <code>". BG_COUNTER_ARCHIVE_RATING ."</code></p></div>";
	else $message .= "<div class='notice notice-error'><p><b>".current_time('mysql')."</b> Ошибка при сохранении данных статистки. См. журнал ошибок: <code>". BG_COUNTER_LOG ."</code></p></div>";

	update_option( 'bg_archive_status', $message);
}
