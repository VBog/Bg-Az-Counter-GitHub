<?php

/*****************************************************************************************
	Функции для вывода рейтинга на странице
	
******************************************************************************************/


/*********************************************************************************
GET /item-score/<path>


Возвращает рейтинг и количество голосов отдельно взятого объекта -
score. Также возвращается флаг, голосовал ли уже данный пользователь.

Пример запроса:

GET /item-score/project/test/author/1

Пример ответа:

{
  "success": true,
  "data": {
    "alreadyVoted":true,
    "score": 3.7142857142857144,
    "votes": 7
  }
}
Если объект не существует, возвращает success: false и data: null.

Важно: если alreadyVoted равно true, то повторная попытка голосования
провалится.
**********************************************************************************/
function bg_az_counter_rating($type, $id) {
	global $project;

	$option = get_option('bg_counter_options');
	if (!$option['rate']) return false;
	if (!$id) return false;
	if ($type == 'post') {
		// Список типов записей имеющих страницу во фронте
		$post_types = get_post_types( [ 'publicly_queryable'=>1 ] );
		$post_types['page'] = 'page';       // встроенный тип не имеет publicly_queryable
		unset( $post_types['attachment'] ); // удалим attachment
		$the_type = get_post_type($id);
		if (!in_array($the_type, $post_types)) return false;
		if (get_post_status($id) != 'publish') return false;
	} else return false;
	
	if ($option['title']) $page_title = $option['title'];
	else $page_title = get_the_title($id);
	if ($option['author']) $author = $option['author'];
	else {
		$author = get_post_meta($id, 'author', true);
		if(!$author) $author = get_bloginfo('name');
	}		
	$result = wp_remote_get (BG_COUNTER_STAT_SCORE.$project."/".$type."/".$id);
	if( is_wp_error( $result ) ) {
		error_log(  PHP_EOL .current_time('mysql')." ITEM-SCORE. Ошибка при получении данных с сервера: ".$result->get_error_message(), 3, BG_COUNTER_LOG );	// сообщение ошибки
		error_log(  " " .$result->get_error_code(), 3, BG_COUNTER_LOG ); 		// ключ ошибки
		return false; 
	}
	
	if (($code = wp_remote_retrieve_response_code( $result )) != 200) {
		error_log(  PHP_EOL .current_time('mysql')." ITEM-SCORE. Сервер вернул код ошибки: ".$code, 3, BG_COUNTER_LOG );	// сообщение ошибки
		error_log(  " " .wp_remote_retrieve_response_message( $result ), 3, BG_COUNTER_LOG ); 		// ключ ошибки
		return false; 
	}

	$json = wp_remote_retrieve_body($result);
	$response = json_decode($json, false);
	if (!$response->success) {
		error_log(  PHP_EOL .current_time('mysql')." ITEM-SCORE. Сервер вернул ответ неудачи:\n".$json, 3, BG_COUNTER_LOG );
		$score = 0;
		$votes = 0;
	} else {
		$data = $response->data;
		$score = number_format((float)$data->score, 1, '.', '');
		$votes = $data->votes;
	}
	$alreadyVoted = 'false'; // Сервер голосовать не должен!!!
	$txt_votes = bg_counter_txt_votes($votes);
	
echo <<<HTML

<div class="bg_counter_rating">
	<div><ul id="bg_counter_rate_box">
		<li></li>
		<li></li>
		<li></li>
		<li></li>
		<li></li>
	</ul>
	<span itemscope="" itemtype="http://schema.org/AggregateRating">
		<meta itemprop="bestRating" content="5" />
		<meta itemprop="worstRating" content="1" />
		<meta itemprop="author" content="{$author}" />
		<meta itemprop="itemReviewed" content="{$page_title}" />

		<meta content="{$score}" itemprop="ratingValue">
		<meta content="{$votes}" itemprop="ratingCount">
		(<span id="bg_counter_votes">{$votes}</span>&nbsp;<span id="bg_counter_votes_txt">{$txt_votes}</span>:&nbsp;<span id="bg_counter_score" data-voted="{$alreadyVoted}">{$score}</span>&nbsp;из&nbsp;<span id="bg_counter_rating_max">5</span>)
	</span></div>
	<div id="bg_counter_popup_help"></div>
</div>
HTML;
}
/*****************************************************************************************
GET /scores-list/<path>

Возвращает непосредственных потомков, упорядоченных по убыванию рейтинга -
score.

Доступные параметры:

limit, offset - аналогично запросу GET /rating/<path>.

Пример запроса:

GET /scores-list/project/test/author/1?limit=3&offset=1

Пример ответа:

{
  "success":true,
  "data":[
    {"id": "4", "type": "book", "score": 4.083333333333333, "votes": 12},
    {"id": "1", "type": "book", "score": 4.0625, "votes": 16},
    {"id": "2", "type": "book", "score": 4.0588235294117645, "votes": 17}
  ]
}
Если объект не существует, возвращает пустой массив потомков.
******************************************************************************************/
function getPostRating ($limit, $offset=0, $number=false) {
	global $project;
	$option = get_option('bg_counter_options');

	$result = wp_remote_get (BG_COUNTER_STAT_SCORELIST.$project."?limit=".$limit."&offset=".$offset."&type=post");
	if( is_wp_error( $result ) ) {
		error_log(  PHP_EOL .current_time('mysql')." SCORES-LIST. Ошибка при получении данных с сервера: ".$result->get_error_message(), 3, BG_COUNTER_LOG );	// сообщение ошибки
		error_log(  " " .$result->get_error_code(), 3, BG_COUNTER_LOG ); 		// ключ ошибки
		return false; 
	}
	
	if (($code = wp_remote_retrieve_response_code( $result )) != 200) {
		error_log(  PHP_EOL .current_time('mysql')." SCORES-LIST. Сервер вернул код ошибки: ".$code, 3, BG_COUNTER_LOG );	// сообщение ошибки
		error_log(  " " .wp_remote_retrieve_response_message( $result ), 3, BG_COUNTER_LOG ); 		// ключ ошибки
		return false; 
	}

	$json = wp_remote_retrieve_body($result);

	$response = json_decode($json, false);
	if ($response->success == true){
		$the_key='getPostRating_key';
		if(false===($quote=get_transient($the_key))) {
			if ($number) $quote = '<ol class="bg-az-top-posts">'. PHP_EOL;
			else $quote = '<ul class="bg-az-top-posts">'. PHP_EOL;
			foreach ($response->data as $p) {
				if ($p->type!='post') {
					error_log(  PHP_EOL .current_time('mysql')." SCORES-LIST. Неверный тип:\n".$p->type, 3, BG_COUNTER_LOG );
					continue;
				}
				$id = intval($p->id);
				$votes = intval($p->votes);
				if (!$id) {
					error_log(  PHP_EOL .current_time('mysql')." SCORES-LIST. Неверный ID:\n".$p->id, 3, BG_COUNTER_LOG );
					continue;
				}
				$post = get_post($id);
				if (!$post) {
					error_log(  PHP_EOL .current_time('mysql')." SCORES-LIST. Нет записи: \n".$p->id, 3, BG_COUNTER_LOG );
					continue;
				}
				$title = $post->post_title;
				$link = '<a href="'. get_permalink($post).'" title="'.$title.'" data-ID="'.$p->id.'" data-type="'.$p->type.'" data-score="'.$p->score.'" data-status="'.$post->post_status.'">'.$title.'</a>';
				$txt_votes = bg_counter_txt_votes($votes);
				$txt_score = $votes.'&nbsp;'.$txt_votes.':&nbsp;'.number_format((float)$p->score, 1, ',', '&nbsp;').'&nbsp;из&nbsp;5';
				$quote .= '<li>'.$link.' -&nbsp;<span class="bg-az-count">'.$txt_score.'</span></li>'. PHP_EOL;
			}
			if ($number) $quote .= '</ol>'. PHP_EOL;
			else $quote .= '</ul>'. PHP_EOL;
			set_transient( $the_key, $quote, $option['period'] );
		}
		return $quote;
	} else {
		error_log(  PHP_EOL .current_time('mysql')." SCORES-LIST. Сервер вернул ответ неудачи:\n".$json, 3, BG_COUNTER_LOG );
		return false;
	}
}
/*****************************************************************************************
	POST /set-rating/<path>

	Устанвливает рейтинг для объекта и создаёт его, если объекта не существовал.

	Пример запроса:

	POST /set-rating/project/test/author/1/book/3

	Тело: {"votes": 10, "ratings": 46}

	Пример ответа:

	{"success": true}
	
	Альтернативная форма запроса для массового импорта:

	POST /set-rating с телом:

	[
	  {"path": "/test/1/author/2", "votes": 10, "ratings": 46},
	  ...
	]
******************************************************************************************/
// Установить счетчик рейтинга
function setRating ($path, $request) {
	global $project;
	
	$result = wp_remote_post (BG_COUNTER_STAT_SET_RATINGS.$project.$path, array('body' => $request));
	if( is_wp_error( $result ) ) {
		echo "<br>".$result->get_error_message();	// сообщение ошибки
		echo "<br>".$result->get_error_code(); 	// ключ ошибки
		error_log(  PHP_EOL .current_time('mysql')." SET-RATING. Ошибка при получении данных с сервера: ".$result->get_error_message(), 3, BG_COUNTER_LOG );	// сообщение ошибки
		error_log(  " " .$result->get_error_code(), 3, BG_COUNTER_LOG ); 		// ключ ошибки
		return false; 
	}

	$json = wp_remote_retrieve_body($result);
	$response = json_decode($json, false);
	if ($response->success)	return true;
	else {
		echo $json; 
		error_log(  PHP_EOL .current_time('mysql')." SET-RATING. Сервер вернул ответ неудачи:\n".$json, 3, BG_COUNTER_LOG );
		return false;
	}
	
}

// Установить ВСЕ счетчики рейтинга
function setAllRatings ($request) {
	global $project;
	
	$result = wp_remote_post (BG_COUNTER_STAT_SET_RATINGS, array('body' => $request));
	if( is_wp_error( $result ) ) {
		echo "<br>".$result->get_error_message();	// сообщение ошибки
		echo "<br>".$result->get_error_code(); 	// ключ ошибки
		error_log(  PHP_EOL .current_time('mysql')." SET-RATINGS (all). Ошибка при получении данных с сервера: ".$result->get_error_message(), 3, BG_COUNTER_LOG );	// сообщение ошибки
		error_log(  " " .$result->get_error_code(), 3, BG_COUNTER_LOG ); 		// ключ ошибки
		return false; 
	}

	$json = wp_remote_retrieve_body($result);
	$response = json_decode($json, false);
	if ($response->success)	return true;
	else {
		echo $json; 
		error_log(  PHP_EOL .current_time('mysql')." SET-RATINGS (all). Сервер вернул ответ неудачи:\n".$json, 3, BG_COUNTER_LOG );
		return false;
	}
	
}


/*****************************************************************************************
	Шорт-коды
	Функции обработки шорт-кода
******************************************************************************************/
// Регистрируем шорт-код bg_az_rating
	add_shortcode( 'bg_az_rating', 'bg_az_rating_shortcode' );
// Регистрируем шорт-код bg_counter_post_rating
	add_shortcode( 'bg_counter_post_rating', 'bg_counter_post_rating_shortcode' );

//  [bg_az_rating]
//	Выводит на экран форму для голосования
function bg_az_rating_shortcode( $atts ) {
	extract( shortcode_atts( array(
		'type' => null,
		'id' => null
	), $atts ) );
	if (is_null($id)) {
		$id = get_the_ID();
	}
	if (is_null($type)) {
		$type = 'post';
	}
	
	$quote = bg_az_counter_rating($type, $id);
	return "{$quote}";
}
//  [bg_counter_post_rating limit='10']
//	Выводит на экран список популярных постов
function bg_counter_post_rating_shortcode( $atts ) {
	global $post;
	extract( shortcode_atts( array(
		'limit' => '10',
		'number' => false
	), $atts ) );
	$quote = getPostRating ($limit, 0, (bool)$number);
	return "{$quote}";
}

// Склоняем слово "голос"
function bg_counter_txt_votes ($votes){
	$m = $votes % 10; 
	$j = $votes % 100;
	if($m==0 || $m>=5 || ($j>=10 && $j<=20)) $txt_votes = 'голосов';
	else if($m>=2 && $m<=4) $txt_votes = 'голоса'; 
	else $txt_votes = 'голос';
	
	return $txt_votes;
}
