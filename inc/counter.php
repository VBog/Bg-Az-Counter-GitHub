<?php
/*****************************************************************************************
GET /rating/<path>

Рейтинг. Возвращает непосредственных потомков, упорядоченных по убыванию
value.

Доступные параметры:

limit - ограничение на количество возвращаемых потомков. По умолчанию: 100
offset - сколько элементов в начале пропустить. По умолчанию: 0

limit/offset работают по такому же принципу, как и в SQL.

Пример запроса:

GET /rating/project/test/author/1?limit=5&offset=3

Пример ответа:

{
  "success":true,
  "data":[
    {"id":"6","type":"book","value":11},
    {"id":"7","type":"book","value":7},
    {"id":"5","type":"book","value":1},
    {"id":"4","type":"book","value":1},
    {"id":"3","type":"book","value":1}
  ]
}
Если счётчик не существует, возвращает пустой массив потомков.
******************************************************************************************/
function getPopularPosts ($limit, $offset=0, $number=false) {
	global $project;
	$option = get_option('bg_counter_options');

	$result = wp_remote_get (BG_COUNTER_STAT_RATING.$project."?limit=".$limit."&offset=".$offset."&type=post");
	if( is_wp_error( $result ) ) {
		error_log(  PHP_EOL .current_time('mysql')." RATING (top posts). Ошибка при получении данных с сервера: ".$result->get_error_message(), 3, BG_COUNTER_LOG );	// сообщение ошибки
		error_log(  " " .$result->get_error_code(), 3, BG_COUNTER_LOG ); 		// ключ ошибки
		return false; 
	}
	
	if (($code = wp_remote_retrieve_response_code( $result )) != 200) {
		error_log(  PHP_EOL .current_time('mysql')." RATING (top posts). Сервер вернул код ошибки: ".$code, 3, BG_COUNTER_LOG );	// сообщение ошибки
		error_log(  " " .wp_remote_retrieve_response_message( $result ), 3, BG_COUNTER_LOG ); 		// ключ ошибки
		return false; 
	}

	$json = wp_remote_retrieve_body($result);

	$response = json_decode($json, false);
	if ($response->success == true){
		$the_key='getPopularPosts_key';
		if(false===($quote=get_transient($the_key))) {
			if ($number) $quote = '<ol class="bg-az-top-posts">'. PHP_EOL;
			else $quote = '<ul class="bg-az-top-posts">'. PHP_EOL;
			foreach ($response->data as $p) {
				if ($p->type!='post') continue;
				$id = intval($p->id);
				if (!$id) continue;
				$post = get_post($id);
				if (!$post) continue;
				$title = $post->post_title;
				$link = '<a href="'. get_permalink($post).'" title="'.$title.'" data-ID="'.$p->id.'" data-type="'.$p->type.'" data-value="'.$p->value.'" data-status="'.$post->post_status.'">'.$title.'</a>';
				$quote .= '<li>'.$link.' - <span class="bg-az-count">'.bg_counter_number_format($p->value).'</span></li>'. PHP_EOL;
			}
			if ($number) $quote .= '</ol>'. PHP_EOL;
			else $quote .= '</ul>'. PHP_EOL;
			set_transient( $the_key, $quote, $option['period'] );
		}
		return $quote;
	} else {
		error_log(  PHP_EOL .current_time('mysql')." RATING (top posts). Сервер вернул ответ неудачи:\n".$json, 3, BG_COUNTER_LOG );
		return false;
	}
}
/*****************************************************************************************
POST /set-counter/<path>

Задаёт значение счётчика и создаёт его, если счётчик не существовал.
Предназначено для импорта данных и должно быть закрыто от внешних клиентов.

Пример запроса:

POST /set-counter/project/test/author/1/book/3

Тело: {"counter": 3}

Пример ответа:

{"success": true}
******************************************************************************************/
// Установить 1 счетчик
function setCount ($path, $counter) {
	global $project;
	
	$result = wp_remote_post (BG_COUNTER_STAT_SET.$project.$path, array('body' => '{"counter": '.$counter.'}'));
	if( is_wp_error( $result ) ) {
		echo "<br>".$result->get_error_message();	// сообщение ошибки
		echo "<br>".$result->get_error_code(); 	// ключ ошибки
		error_log(  PHP_EOL .current_time('mysql')." SET-COUNTER. Ошибка при получении данных с сервера: ".$result->get_error_message(), 3, BG_COUNTER_LOG );	// сообщение ошибки
		error_log(  " " .$result->get_error_code(), 3, BG_COUNTER_LOG ); 		// ключ ошибки
		return false; 
	}

	$json = wp_remote_retrieve_body($result);
	$response = json_decode($json, false);
	if ($response->success)	return true;
	else {
		echo $json; 
		error_log(  PHP_EOL .current_time('mysql')." SET-COUNTER. Сервер вернул ответ неудачи:\n".$json, 3, BG_COUNTER_LOG );
		return false;
	}
	
}
// Установить ВСЕ счетчики проекта
function setAllCounts ($request) {
	global $project;
	
	$result = wp_remote_post (BG_COUNTER_STAT_SET, array('body' => $request));
	if( is_wp_error( $result ) ) {
		echo "<br>".$result->get_error_message();	// сообщение ошибки
		echo "<br>".$result->get_error_code(); 	// ключ ошибки
		error_log(  PHP_EOL .current_time('mysql')." SET-COUNTER (all). Ошибка при получении данных с сервера: ".$result->get_error_message(), 3, BG_COUNTER_LOG );	// сообщение ошибки
		error_log(  " " .$result->get_error_code(), 3, BG_COUNTER_LOG ); 		// ключ ошибки
		return false; 
	}

	$json = wp_remote_retrieve_body($result);
	$response = json_decode($json, false);
	if ($response->success)	return true;
	else {
		echo $json; 
		error_log(  PHP_EOL .current_time('mysql')." SET-COUNTER (all). Сервер вернул ответ неудачи:\n".$json, 3, BG_COUNTER_LOG );
		return false;
	}
	
}


/*****************************************************************************************
	Отображает разметку текста для отображения кол-ва просмотров 
	и кол-ва читающих пост пользователей
******************************************************************************************/
function bg_az_counter_views ($type=null, $id=null, $now=null, $rate=null, $views=null, $pr=null) {
	global $project;
	$option = get_option('bg_counter_options');
	
	if (is_null($type)) {
		if (is_single() || is_page()) {	// Только записи и страницы
			$post = get_post();
			if ($post->post_status == 'publish') {	// Только опубликованные
				$id = $post->ID;
				$type = 'post';
			} else return false;
		} elseif (is_category()) {
			$id = get_query_var('cat');
			$type = 'category';
		} elseif (is_tag()) {
			$id = get_query_var('tag_id');
			$type = 'tag';
		} elseif (is_home()) {
			$id = 1;
			$type = 'index';
		} else return false;
	}
	if (is_null($id)) {
		if ($type == 'post') {	// Только записи и страницы
			$post = get_post();
			if ($post->post_status == 'publish') {	// Только опубликованные
				$id = $post->ID;
			} else return false;
		} elseif ($type == 'category') {
			$cat = get_the_category();
			$id = $cat->cat_ID;
		} elseif ($type == 'tag') {
			$tags = get_the_tags();
			$id = $tags->term_id ;
		} elseif ($type == 'index') {
			$id = 1;
		} else return false;
	}
	if (is_null($views)) {
		$views = $option['views'];
	}
	if (is_null($now)) {
		$now = $option['now'];
	}
	if (is_null($rate)) {
		$rate = $option['rate'];
	}
	if ($type != 'post') $rate = null;
	
	if ($id) {
		$link = get_permalink($id);
		// Получить имя проекта по ссылке
		if (wp_parse_url( $link, PHP_URL_HOST ) == 'azbyka.ru') {
			$proj = wp_parse_url( dirname($link), PHP_URL_PATH );
			$proj = ltrim($proj, '/');
			if (!$proj) $proj = 'main';	// Главный сайт 
			else list($proj) = explode ('/', $proj);
		} else {
			$proj = dirname($link);
			$proj = wp_parse_url( $proj, PHP_URL_HOST ).wp_parse_url( $proj, PHP_URL_PATH );
			$proj = preg_replace('#[\.\/\\\\]#i', '_', $proj) ;
		}
		$proj = '/project/'.$proj;
		if (is_null($pr) && (($project != $proj) || get_post_meta( $id, 'link', true ))) $id = null;// Заглушка для ссылок на другие проекты
	}
	
	if (!is_null($type) && !is_null($id)) {
		$sum = '<i title="Всего просмотров" class="fa fa-eye"></i> <span class="bg-az-counter-views"></span>'; 
		$users = ' <i title="Сейчас читают" class="fa fa-user-o"></i> <span class="bg-az-counter-now"></span>'; 
		$score = ' <i title="Оценка пользователей" class="fa fa-star-o"></i> <span class="bg-az-counter-score"></span>';
		if (!is_null($pr)) $dataPr = ' data-project="'.$pr.'"';
		else $dataPr = "";
		$quote = '<span class="bg-az-counter"'.$dataPr.' data-type="'.$type.'" data-ID="'.$id.'">'.($views?$sum:'').($now?$users:'').($rate?$score:'').'</span>';
		return $quote;
	} else return "";
}	

/*****************************************************************************************
	Шорт-коды
	Функции обработки шорт-кодов
******************************************************************************************/
// Регистрируем шорт-код bg_counter
	add_shortcode( 'bg_counter', 'bg_counter_shortcode' );
// Регистрируем шорт-код bg_counter_top_posts
	add_shortcode( 'bg_counter_top_posts', 'bg_counter_top_posts_shortcode' );

//  [bg_counter type='post' id='1234' now='true']
//	Выводит на экран разметку счетчика
function bg_counter_shortcode( $atts ) {
	global $post;
	extract( shortcode_atts( array(
		'project' => null,
		'type' => null,
		'id' => null,
		'views' => null,
		'now' => null,
		'rate' => null
	), $atts ) );
	$quote = bg_az_counter_views ($type, $id, $now, $rate, $views, $project);
	return "{$quote}";
}

//  [bg_counter_top_posts limit='10']
//	Выводит на экран список популярных постов
function bg_counter_top_posts_shortcode( $atts ) {
	global $post;
	extract( shortcode_atts( array(
		'limit' => '10',
		'number' => false
	), $atts ) );
	$quote = getPopularPosts ($limit, 0, (bool)$number);
	return "{$quote}";
}

function bg_counter_number_format ($num) {
	$num = floatval ($num);
	if ($num > 1000000000.0) {
		$num = round($num/1000000000.0, 1)."&nbsp;млрд.";
	} elseif ($num > 1000000.0) {
		$num = round($num/1000000.0, 1)."&nbsp;млн.";
	} elseif ($num > 10000.0) {
		$num = round($num/1000.0, 1)."&nbsp;тыс.";
	} else {
		$num = number_format($num, 0, ',', '&nbsp;');
	}
	return $num;
}
