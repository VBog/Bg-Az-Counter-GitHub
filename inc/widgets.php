<?php
/*****************************************************************************************
	Виджет отображает в сайдбаре 
	Список популярных постов
	
******************************************************************************************/
class bg_counter_TopPostsWidget extends WP_Widget {
 
	// создание виджета
	function __construct() {
		parent::__construct(
			'bg_counter_top_widget', 
			'Популярные записи', // заголовок виджета
			array( 'description' => 'Bg Az-Counter: Позволяет вывести записи, отсортированные по количеству просмотров.' ) // описание
		);
	}
 
	// фронтэнд виджета
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] ); // к заголовку применяем фильтр (необязательно)
		$posts_per_page = $instance['posts_per_page'];
 
		echo $args['before_widget'];
 
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
 
		$list = getPopularPosts ($posts_per_page);
		if ($list) {
?>
	<div class="widget-item">
		<div class="widget-inner">
			<?php echo $list; ?>
		</div>
	</div>
<?php
		}
		echo $args['after_widget'];
	}
 
	// бэкэнд виджета
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		if ( isset( $instance[ 'posts_per_page' ] ) ) {
			$posts_per_page = $instance[ 'posts_per_page' ];
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Заголовок</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'posts_per_page' ); ?>">Количество записей:</label> 
			<input id="<?php echo $this->get_field_id( 'posts_per_page' ); ?>" name="<?php echo $this->get_field_name( 'posts_per_page' ); ?>" type="text" value="<?php echo ($posts_per_page) ? esc_attr( $posts_per_page ) : '5'; ?>" size="3" />
		</p>
		<?php 
	}
 
	// сохранение настроек виджета
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['posts_per_page'] = ( is_numeric( $new_instance['posts_per_page'] ) ) ? $new_instance['posts_per_page'] : '5'; // по умолчанию выводятся 5 постов
		return $instance;
	}
}
 
/*****************************************************************************************
	Виджет отображает в сайдбаре 
	Количество онлайн посетителей на сайте,  
	общее количество просмотров и количество записей
	
******************************************************************************************/
class bg_counter_OnlineNowWidget extends WP_Widget {
 
	// создание виджета
	function __construct() {
		parent::__construct(
			'bg_counter_online_widget', 
			'Сейчас на сайте', // заголовок виджета
			array( 'description' => 'Bg Az-Counter: Количество посетителей на сайте.' ) // описание
		);
	}
 
	// фронтэнд виджета
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] ); // к заголовку применяем фильтр (необязательно)
		$subtitle1 = $instance['subtitle1'];
		$unit1 = $instance['unit1'];
		$subtitle2 = $instance['subtitle2'];
		$subtitle3 = $instance['subtitle3'];
 
		echo $args['before_widget'];
 
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
 
?>
	<div class="widget-item">
		<div class="widget-inner">
			<span class="bg-az-counter">
				<p><?php echo $subtitle1; ?>: <span class="bg-az-counter-now"></span> <?php echo $unit1; ?></p>
				<p><?php echo $subtitle2; ?>: <span class="bg-az-counter-views"></span></p>
				<p><?php echo $subtitle3; ?>: <span class="bg-az-counter-posts"><?php echo wp_count_posts()->publish; ?></span></p>

			</span>
		</div>
	</div>
<?php
		echo $args['after_widget'];
	}
 
	// бэкэнд виджета
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
			$subtitle1 = $instance['subtitle1'];
			$unit1 = $instance['unit1'];
			$subtitle2 = $instance['subtitle2'];
			$subtitle3 = $instance['subtitle3'];
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Заголовок</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'subtitle1' ); ?>">Сейчас на сайте:</label> 
			<input id="<?php echo $this->get_field_id( 'subtitle1' ); ?>" name="<?php echo $this->get_field_name( 'subtitle1' ); ?>" type="text" value="<?php echo ($subtitle1) ? esc_attr( $subtitle1 ) : 'Сейчас на сайте'; ?>" />
			<input id="<?php echo $this->get_field_id( 'unit1' ); ?>" name="<?php echo $this->get_field_name( 'unit1' ); ?>" type="text" value="<?php echo ($unit1) ? esc_attr( $unit1 ) : 'чел.'; ?>" size="10" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'subtitle2' ); ?>">Всего просмотров:</label> 
			<input id="<?php echo $this->get_field_id( 'subtitle2' ); ?>" name="<?php echo $this->get_field_name( 'subtitle2' ); ?>" type="text" value="<?php echo ($subtitle2) ? esc_attr( $subtitle2 ) : 'Всего просмотров'; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'subtitle3' ); ?>">Всего записей:</label> 
			<input id="<?php echo $this->get_field_id( 'subtitle3' ); ?>" name="<?php echo $this->get_field_name( 'subtitle3' ); ?>" type="text" value="<?php echo ($subtitle3) ? esc_attr( $subtitle3 ) : 'Всего записей'; ?>" />
		</p>
		<?php 
	}
 
	// сохранение настроек виджета
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['subtitle1'] = ( ! empty( $new_instance['subtitle1'] ) ) ? strip_tags( $new_instance['subtitle1'] ) : 'Сейчас на сайте';
		$instance['unit1'] = ( ! empty( $new_instance['unit1'] ) ) ? strip_tags( $new_instance['unit1'] ) : 'чел.';
		$instance['subtitle2'] = ( ! empty( $new_instance['subtitle2'] ) ) ? strip_tags( $new_instance['subtitle2'] ) : 'Всего просмотров';
		$instance['subtitle3'] = ( ! empty( $new_instance['subtitle3'] ) ) ? strip_tags( $new_instance['subtitle3'] ) : 'Всего записей';
		return $instance;
	}
}
/*****************************************************************************************
	Виджет отображает в сайдбаре 
	Список популярных постов по результатам голосования
	
******************************************************************************************/
class bg_counter_PostRatingWidget extends WP_Widget {
 
	// создание виджета
	function __construct() {
		parent::__construct(
			'bg_counter_post_rating_widget', 
			'Популярные записи', // заголовок виджета
			array( 'description' => 'Bg Az-Counter: Позволяет вывести записи, отсортированные по количеству голосов.' ) // описание
		);
	}
 
	// фронтэнд виджета
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] ); // к заголовку применяем фильтр (необязательно)
		$posts_per_page = $instance['posts_per_page'];
 
		echo $args['before_widget'];
 
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
 
		$list = getPostRating ($posts_per_page);
		if ($list) {
?>
	<div class="widget-item">
		<div class="widget-inner">
			<?php echo $list; ?>
		</div>
	</div>
<?php
		}
		echo $args['after_widget'];
	}
 
	// бэкэнд виджета
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		if ( isset( $instance[ 'posts_per_page' ] ) ) {
			$posts_per_page = $instance[ 'posts_per_page' ];
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Заголовок</label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'posts_per_page' ); ?>">Количество записей:</label> 
			<input id="<?php echo $this->get_field_id( 'posts_per_page' ); ?>" name="<?php echo $this->get_field_name( 'posts_per_page' ); ?>" type="text" value="<?php echo ($posts_per_page) ? esc_attr( $posts_per_page ) : '5'; ?>" size="3" />
		</p>
		<?php 
	}
 
	// сохранение настроек виджета
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['posts_per_page'] = ( is_numeric( $new_instance['posts_per_page'] ) ) ? $new_instance['posts_per_page'] : '5'; // по умолчанию выводятся 5 постов
		return $instance;
	}
}
 
 
/*****************************************************************************************
	Регистрация виджетов
	
******************************************************************************************/
function bg_counter_widgets_load() {
	register_widget( 'bg_counter_TopPostsWidget' );
	register_widget( 'bg_counter_OnlineNowWidget' );
	register_widget( 'bg_counter_PostRatingWidget' );
}
add_action( 'widgets_init', 'bg_counter_widgets_load' );