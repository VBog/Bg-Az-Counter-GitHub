<?php
/******************************************************************************************
	Страница настроек плагина
	
*******************************************************************************************/
// Начальные значения
add_option('bg_counter_options', array('period'=>DAY_IN_SECONDS, 'update'=>3000, 'maxreconnect'=>5, 'views'=>1, 'now'=>1, 'rate'=>1, 'title'=>'', 'author'=>'', 'debug'=>0, 'archive'=>0, 'pvc'=>0, 'wppp'=>0, 'wppm'=>0, 'bgar'=>0));
add_option('bg_pvc_loaded', '');
add_option('bg_wppp_loaded', '');
add_option('bg_wppm_loaded', '');
add_option('bg_bgar_loaded', '');
$val = get_option('bg_counter_options');
if (!isset($val['period'])) {
	$val['period'] = DAY_IN_SECONDS;
	update_option( 'bg_counter_period', $val['period'] );
}	
if (!isset($val['update'])) {
	$val['update'] = 3000;
	update_option( 'bg_counter_period', $val['update'] );
}	
if (!isset($val['maxreconnect'])) {
	$val['maxreconnect'] = 5;
	update_option( 'bg_counter_maxreconnect', $val['maxreconnect'] );
}	
if (!isset($val['views'])) {
	$val['views'] = 0;
}	
if (!isset($val['now'])) {
	$val['now'] = 0;
}	
if (!isset($val['rate'])) {
	$val['rate'] = 0;
}	
if (!isset($val['title'])) {
	$val['title'] = "";
}	
if (!isset($val['author'])) {
	$val['author'] = "Редакция портала Азбука веры";
}	
if (!isset($val['debug'])) {
	$val['debug'] = 0;
}	
if (!isset($val['archive'])) {
	$val['archive'] = 0;
}	
if (!isset($val['pvc'])) {
	$val['pvc'] = 0;
}	
if (!isset($val['wppp'])) {
	$val['wppp'] = 0;
}	
if (!isset($val['wppm'])) {
	$val['wppm'] = 0;
}	
if (!isset($val['bgar'])) {
	$val['bgar'] = 0;
}	
update_option( 'bg_counter_options', $val );

add_action('admin_menu', 'bg_counter_add_plugin_page');

function bg_counter_add_plugin_page(){
	add_options_page( 'Настройки Bg Az-Counter', 'Bg Az-Counter', 'manage_options', 'bg_counter_slug', 'bg_counter_options_page_output' );
}

function bg_counter_options_page_output(){
	$val = get_option('bg_counter_options');
	echo "<br><div class='notice notice-info'><p>Plugin powered by Redis-based server for collecting view statistics. <a href='https://gitlab.eterfund.ru/azbyka/stats-server' target='_blank'>See details here</a>. Server URL: <code>https://stat.azbyka.ru</code></p></div>";

	$pvc_loaded = get_option('bg_pvc_loaded');
	$wppp_loaded = get_option('bg_wppp_loaded');
	$wppm_loaded = get_option('bg_wppm_loaded');
	$bgar_loaded = get_option('bg_bgar_loaded');
//	Post Views Counter
	if ((isset($pvc_loaded) && $pvc_loaded != 'on') && (isset($val['pvc']) && $val['pvc']) ) {
		echo '<h2>Загрузка данных из Post Views Counter:</h2>'; 
		$bg_from_pvc_result = bg_counter_getPostViewsCounter ();
		$val['pvc'] = 0;
		update_option( 'bg_counter_options', $val );
		if ($bg_from_pvc_result === false) {
			echo "<br><div class='notice notice-error'><p><b>ОШИБКА</b> при отправке данных на сервер.</p></div>";
		} else {
			update_option('bg_pvc_loaded','on');
			echo "<br><div class='notice notice-success'><p>Все данные ($bg_from_pvc_result) из плагина <b>Post Views Counter</b> перенесены на сервер.</p></div>";	
		}
		submit_button( "Продолжить...", "", "", true, "onclick='location.reload();'" );
//	WP Popular Posts
	}else if ((isset($wppp_loaded) && $wppp_loaded != 'on') && (isset($val['wppp']) && $val['wppp']) ) {
		echo '<h2>Загрузка данных из WP Popular Posts:</h2>'; 
		$bg_from_wppp_result = bg_counter_getWPPopularPosts ();
		$val['wppp'] = 0;
		update_option( 'bg_counter_options', $val );
		if ($bg_from_wppp_result === false) {
			echo "<br><div class='notice notice-error'><p><b>ОШИБКА</b> при отправке данных на сервер.</p></div>";
		} else {
			update_option('bg_wppp_loaded','on');
			echo "<br><div class='notice notice-success'><p>Все данные ($bg_from_wppp_result) из плагина <b>WP Popular Posts</b> перенесены на сервер.</p></div>";	
		}
		submit_button( "Продолжить...", "", "", true, "onclick='location.reload();'" );
//	WP post meta "views"
	} else if ((isset($wppm_loaded) && $wppm_loaded != 'on') && (isset($val['wppm']) && $val['wppm']) ) {
		echo '<h2>Загрузка данных из WP post meta "views":</h2>'; 
		$bg_from_wppm_result = bg_counter_getWPPostMeta ();
		$val['wppm'] = 0;
		update_option( 'bg_counter_options', $val );
		if ($bg_from_wppm_result === false) {
			echo "<br><div class='notice notice-error'><p><b>ОШИБКА</b> при отправке данных на сервер.</p></div>";
		} else {
			update_option('bg_wppm_loaded','on');
			echo "<br><div class='notice notice-success'><p>Все данные ($bg_from_wppm_result) из произвольных полей <b>views</b> постов перенесены на сервер.</p></div>";	
		}
		submit_button( "Продолжить...", "", "", true, "onclick='location.reload();'" );
//	Bg Az-Rating
	} else if ((isset($bgar_loaded) && $bgar_loaded != 'on') && (isset($val['bgar']) && $val['bgar']) ) {
		echo '<h2>Загрузка данных из Bg Az-Rating:</h2>'; 
		$bg_from_bgar_result = bg_counter_getBgAzRating ();
		$val['bgar'] = 0;
		update_option( 'bg_counter_options', $val );
		if ($bg_from_bgar_result === false) {
			echo "<br><div class='notice notice-error'><p><b>ОШИБКА</b> при отправке данных на сервер.</p></div>";
		} else {
			update_option('bg_bgar_loaded','on');
			echo "<br><div class='notice notice-success'><p>Все данные ($bg_from_bgar_result) из плагина <b>Bg Az-Rating</b> постов перенесены на сервер.</p></div>";	
		}
		submit_button( "Продолжить...", "", "", true, "onclick='location.reload();'" );
//	Архивы
	} else if ((isset($val['archive']) && $val['archive']) ) {
		echo '<h2>Загрузка данных из архивов '.BG_COUNTER_ARCHIVE_COUNTER.' и '.BG_COUNTER_ARCHIVE_RATING.':</h2>'; 
		$bg_from_archive_result = bg_counter_sendCounterArchiveData ();
		if ($bg_from_archive_result === false) {
			echo "<br><div class='notice notice-error'><p><b>".BG_COUNTER_ARCHIVE_COUNTER.":</b> <i>ОШИБКА</i> при отправке данных на сервер.</p></div>";
		} else {
			echo "<br><div class='notice notice-success'><p>Все данные ($bg_from_archive_result) из архива <b>".BG_COUNTER_ARCHIVE_COUNTER."</b> перенесены на сервер.</p></div>";	
		}
		$bg_from_archive_result = bg_counter_sendRatingArchiveData ();
		if ($bg_from_archive_result === false) {
			echo "<br><div class='notice notice-error'><p><b>".BG_COUNTER_ARCHIVE_RATING.":</b> <i>ОШИБКА</i> при отправке данных на сервер.</p></div>";
		} else {
			echo "<br><div class='notice notice-success'><p>Все данные ($bg_from_archive_result) из архива <b>".BG_COUNTER_ARCHIVE_RATING."</b> перенесены на сервер.</p></div>";	
		}
		$val['archive'] = 0;
		update_option( 'bg_counter_options', $val );
		submit_button( "Продолжить...", "", "", true, "onclick='location.reload();'" );
	} else {
		if ((isset($val['period']) && $val['period'] != get_option('bg_counter_period')) ) {
			delete_transient( 'getPopularPosts_key' );
			delete_transient( 'getPostRating_key' );
			update_option( 'bg_counter_period', $val['period'] );
		}
		$archive_status = get_option('bg_archive_status');
		if ($archive_status) echo "<br>".$archive_status;

	?>
	<div class="wrap">
		<h2><?php echo get_admin_page_title() ?></h2>
		<p>Версия <b><?php echo	BG_COUNTER_VERSION; ?></b></p>
		<form action="options.php" method="POST">
		<?php
			settings_fields( 'bg_counter_option_group' );	// скрытые защитные поля
			do_settings_sections( 'bg_counter_page' ); 		// секции с настройками (опциями) 'section_1'
			submit_button();
		?>
		</form>
	</div>
	<script>
	// Блокируем изменение всх опций, кроме этой
		function blockInputs(el) {
			if (jQuery(el).prop ('checked')) {
				jQuery('select').prop ('disabled', true);
				jQuery('input:text').prop ('disabled', true);
				jQuery('input:checkbox').prop ('disabled', true);
			} else {
				jQuery('select').prop ('disabled', false);
				jQuery('input:text').prop ('disabled', false);
				jQuery('input:checkbox').prop ('disabled', false);
			}
			jQuery(el).prop ('disabled', false);
		}
	</script>
	<?php
	}
}

/**
 * Регистрируем настройки.
 * Настройки будут храниться в массиве, а не одна настройка = одна опция.
 */
add_action('admin_init', 'bg_counter_settings');
function bg_counter_settings(){
	// параметры: $option_group, $option_name, $sanitize_callback
	register_setting( 'bg_counter_option_group', 'bg_counter_options', 'bg_counter_sanitize_callback' );

	// параметры: $id, $title, $callback, $page
	add_settings_section( 'section_1','Основные параметры', '', 'bg_counter_page' ); 
	add_settings_section( 'section_2', 'Импорт данных', '', 'bg_counter_loaded' ); 

	// параметры: $id, $title, $callback, $page, $section, $args
	add_settings_field('bg_counter_period', 'Периодичность обработки данных', 'fill_bg_counter_period', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_update', 'Периодичность обновления счетчиков online-посетителей', 'fill_bg_counter_update', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_maxreconnect', 'Максимальное количество повторов запросов', 'fill_bg_counter_maxreconnect', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_views', 'Просмотров всего', 'fill_bg_counter_views', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_now', 'Просматривают сейчас', 'fill_bg_counter_now', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_rate', 'Оценка пользователями', 'fill_bg_counter_rate', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_title', 'Общее название в сниппете по умолчанию', 'fill_bg_counter_title', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_author', 'Общее имя автора в сниппете по умолчанию', 'fill_bg_counter_author', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_debug', 'Включить отладку', 'fill_bg_counter_debug', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_archive', 'Загрузить данные из архива на сервер', 'fill_bg_counter_sendArchive', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_pvc', 'Загрузить данные из Post Views Counter', 'fill_bg_counter_pvc', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_wppp', 'Загрузить данные из WP Popular Posts', 'fill_bg_counter_wppp', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_wppm', 'Загрузить данные из произвольных полей "views"', 'fill_bg_counter_wppm', 'bg_counter_page', 'section_1' );
	add_settings_field('bg_counter_bgar', 'Загрузить данные из Bg Az-Rating', 'fill_bg_counter_bgar', 'bg_counter_page', 'section_1' );
	
}

## Заполняем опцию 1a
function fill_bg_counter_period(){
	$val = get_option('bg_counter_options');
	$val = $val ? $val['period'] : DAY_IN_SECONDS; 
	?>
	<label>
	<select name="bg_counter_options[period]">
		<option value="1" <?php selected( 1, $val ); ?>>мгновенно</option>
		<option value="<?php echo HOUR_IN_SECONDS; ?>" <?php selected( HOUR_IN_SECONDS, $val ); ?>>каждый час</option>
		<option value="<?php echo DAY_IN_SECONDS; ?>" <?php selected( DAY_IN_SECONDS, $val ); ?>>ежедневно</option>
		<option value="<?php echo WEEK_IN_SECONDS; ?>" <?php selected( WEEK_IN_SECONDS, $val ); ?>>еженедельно</option>
		<option value="<?php echo MONTH_IN_SECONDS; ?>" <?php selected( MONTH_IN_SECONDS, $val ); ?>>ежемесячно</option>
		<option value="<?php echo YEAR_IN_SECONDS; ?>" <?php selected( YEAR_IN_SECONDS, $val ); ?>>ежегодно</option>
	</select> (обновление списка популярных постов)</label>
	<?php
}

## Заполняем опцию 1b
function fill_bg_counter_update(){
	$val = get_option('bg_counter_options');
	$val = $val['update']; 
	?>
	<input type="number" name="bg_counter_options[update]" value="<?php echo esc_attr( $val ) ?>" step="100" min="500" /> мсек.
	<?php
}

## Заполняем опцию 1c
function fill_bg_counter_maxreconnect(){
	$val = get_option('bg_counter_options');
	$val = $val['maxreconnect']; 
	?>
	<input type="number" name="bg_counter_options[maxreconnect]" value="<?php echo esc_attr( $val ) ?>" step="1" min="1" />
	<?php
}

## Заполняем опцию 2a
function fill_bg_counter_views(){
	$val = get_option('bg_counter_options');
	$val = $val ? $val['views'] : null;
	?>
	<label><input type="checkbox" name="bg_counter_options[views]" value="1" <?php checked(1, $val ); ?>/> (отображать общее количество просмотров записи)</label>
	<?php
}
## Заполняем опцию 2b
function fill_bg_counter_now(){
	$val = get_option('bg_counter_options');
	$val = $val ? $val['now'] : null;
	?>
	<label><input type="checkbox" name="bg_counter_options[now]" value="1" <?php checked(1, $val ); ?>/> (отображать количество пользователей, просматривающих запись в данный момент)</label>
	<?php
}

## Заполняем опцию 2c
function fill_bg_counter_rate(){
	$val = get_option('bg_counter_options');
	$val = $val ? $val['rate'] : null;
	?>
	<label><input type="checkbox" name="bg_counter_options[rate]" value="1" <?php checked(1, $val ); ?>/> (отображать оценку пользователями записи)</label>
	<?php
}

## Заполняем опцию 3
function fill_bg_counter_title(){
	$val = get_option('bg_counter_options');
	$val = $val['title']; 
	?>
	<input type="text" name="bg_counter_options[title]" value="<?php echo esc_attr( $val ) ?>" size="60" /><br>
	(если не указано, то по умолчанию название поста)
	<?php
}

## Заполняем опцию 4
function fill_bg_counter_author(){
	$val = get_option('bg_counter_options');
	$val = $val['author']; 
	?>
	<input type="text" name="bg_counter_options[author]" value="<?php echo esc_attr( $val ) ?>" size="60" /><br>
	(если не указано, то по умолчанию имя автора в метаполе 'author' поста,<br>если же и метаполе отсутствует, то название сайта)
	<?php
}

## Заполняем опцию 5
function fill_bg_counter_debug(){
	$val = get_option('bg_counter_options');
	$val = $val ? $val['debug'] : null;
	?>
	<label><input type="checkbox" name="bg_counter_options[debug]" value="1" <?php checked(1, $val ); ?>/> отметьте, чтобы в консоли отображалась отладочная информация </label>
	<?php
}

## Заполняем опцию 6
function fill_bg_counter_sendArchive(){
	
	// Всегда предлагать сохранить не отмеченный
	?>
	<label><input type="checkbox" name="bg_counter_options[archive]" value="1" onchange='blockInputs(this);' /> отметьте и нажмите кнопку «Сохранить изменения» </label>
	<?php
}
## Заполняем опцию 7
function fill_bg_counter_pvc(){
	global $wpdb;
	
	$bg_pvc_table = $wpdb->prefix.'post_views';
	if($wpdb->get_var("SHOW TABLES LIKE '$bg_pvc_table'") != $bg_pvc_table) {
		echo "<i>Таблица данных из Post Views Counter отсутствует в текущей БД.</i>";
	} else {
		$val = get_option('bg_counter_options');
		$pvc_loaded = get_option('bg_pvc_loaded');
		if (isset($pvc_loaded) && $pvc_loaded == 'on'){
			echo "<i>Данные из Post Views Counter уже загружены на сервер.</i>";
		} else {
		// Всегда предлагать сохранить не отмеченный
		?>
		<label><input type="checkbox" name="bg_counter_options[pvc]" value="1" onchange='blockInputs(this);' /> отметьте и нажмите кнопку «Сохранить изменения» </label>
		<?php
		}
	}
}
## Заполняем опцию 8
function fill_bg_counter_wppp(){
	global $wpdb;
	
	$bg_wppp_table = $wpdb->prefix.'popularpostsdata';
	if($wpdb->get_var("SHOW TABLES LIKE '$bg_wppp_table'") != $bg_wppp_table) {
		echo "<i>Таблица данных из WP Popular Posts отсутствует в текущей БД.</i>";
	} else {
		$val = get_option('bg_counter_options');
		$wppp_loaded = get_option('bg_wppp_loaded');
		if (isset($wppp_loaded) && $wppp_loaded == 'on'){
			echo "<i>Данные из WP Popular Posts уже загружены на сервер.</i>";
		} else {
		// Всегда предлагать сохранить не отмеченный
		?>
		<label><input type="checkbox" name="bg_counter_options[wppp]" value="1" onchange='blockInputs(this);' /> отметьте и нажмите кнопку «Сохранить изменения» </label>
		<?php
		}
	}
}
## Заполняем опцию 9
function fill_bg_counter_wppm(){
	
	$val = get_option('bg_counter_options');
	$wppm_loaded = get_option('bg_wppm_loaded');
	if (isset($wppm_loaded) && $wppm_loaded == 'on'){
		echo '<i>Данные из произвольных полей "views" уже загружены на сервер.</i>';
	} else {
	// Всегда предлагать сохранить не отмеченный
	?>
	<label><input type="checkbox" name="bg_counter_options[wppm]" value="1" onchange='blockInputs(this);' /> отметьте и нажмите кнопку «Сохранить изменения» </label>
	<?php
	}
}

## Заполняем опцию 10
function fill_bg_counter_bgar(){
	
	$val = get_option('bg_counter_options');
	$bgar_loaded = get_option('bg_bgar_loaded');
	if (isset($bgar_loaded) && $bgar_loaded == 'on'){
		echo '<i>Данные из Bg Az-Rating уже загружены на сервер.</i>';
	} else {
	// Всегда предлагать сохранить не отмеченный
	?>
	<label><input type="checkbox" name="bg_counter_options[bgar]" value="1" onchange='blockInputs(this);' /> отметьте и нажмите кнопку «Сохранить изменения» </label>
	<?php
	}
}

## Очистка данных
function bg_counter_sanitize_callback( $options ){ 
	// очищаем
	foreach( $options as $name => &$val ){
		
		if( $name == 'title' || $name == 'author')
			$val = strip_tags( $val );
		else
			$val = intval( $val );
	}
	return $options;
}
