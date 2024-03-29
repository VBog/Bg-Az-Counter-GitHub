Плагин **Bg Az-Counter** обеспечивает работу клиентской части системы подсчета количества посещений страниц на базе Redis-based сервера `https://stat.azbyka.ru`. 
Код и описание работы сервера [см. здесь](https://gitlab.eterfund.ru/azbyka/stats-server). 

## Плагин выполняет следующие функции:

### 1. Передает на сервер информацию об открытии страницы пользователем
### 2. Запрашивает сервер и отображает на странице количество пользователей, просматривающих в данный момент страницу, общее количество просмотров страницы и оценку страницы пользователями.

Для этого используйте шорт-код `[bg_counter project='main' type='post' id='1234' now='true' rate='true' views='true']` или php-функцию `bg_az_counter_views ($type, $id, $now, $rate, $views, $project);` в шаблоне записи.

где 

* ***project*** - имя проекта: например, для https://azbyka.ru/audio - "audio", для https://azbyka.ru - "main",  для https://sueverie.net - "sueverie_net", то есть url проекта без схемы, слеш и точка заменены на подчеркивание.
* ***type*** - тип записи (пока поддерживается только *post*, но при желании можно сделать *category*, *mark* и что-нибудь еще).
* ***ID*** - ID записи (объекта).
* ***now = true*** (или любое значение кроме null, false, 0, "") - отображать количество пользователей, просматривающих в данный момент страницу.
* ***rate = true*** (или любое значение кроме null, false, 0, "") - отображать оценку пользователями данной записи.
* ***views = true*** (или любое значение кроме null, false, 0, "") - отображать общее количество просмотров страницы.

По умолчанию ***project = null*** (или отсутствует) - соответствует текущему проекту.

***project = <пусто>*** или ***'/'*** - суммарный показатель счетчиков всех проектов. В этом случае  ***type*** и ***ID*** игнорируются.

Если ***type = <пусто>*** и/или ***ID = <пусто>***, то будет выведено общее количество просмотров и онлайн-посетителей на сайте.

Вставляет на страницу HTML-разметку:

```html
<span class="bg-az-counter" data-type="main" data-type="post" data-ID="1234">
	<span class="bg-az-counter-views"></span>
	<span class="bg-az-counter-now"></span>
	<span class="bg-az-counter-score"></span>
</span>
```

Задавайте функцию `bg_az_counter_views` в шаблоне страниц так: 

```php
<?php if (function_exists('bg_az_counter_views')) {echo bg_az_counter_views ('post', $post->ID);} ?>
```

###  3. Создает форму для оценки пользователем поста

Для вставки формы на страницу используйте шорт-код `[bg_az_rating type='post' id='1234']` или php-функцию `bg_az_counter_rating ($type, $id);` в шаблоне записи.

Параметры ***type*** и ***ID*** те же, что и для счетчика просмотров страницы и описаны выше. 

По умолчанию ***type = 'post'*** и ***ID = < ID текущего поста>***.
 
Задавайте функцию `bg_az_counter_views` в шаблоне страниц так: 

```php
<?php if (function_exists('bg_az_counter_rating')) {echo bg_az_counter_rating ('post', $post->ID);} ?>
```

Форма также формирует снипеты для поисковых ботов:

```html
	<span itemscope="" itemtype="http://schema.org/AggregateRating">
		<meta itemprop="bestRating" content="5" />
		<meta itemprop="worstRating" content="1" />
		<meta itemprop="author" content="{$author}" />
		<meta itemprop="itemReviewed" content="{$page_title}" />
		<meta content="{$score}" itemprop="ratingValue">
		<meta content="{$votes}" itemprop="ratingCount">
	</span> 
```

* ***{$page_title}*** - если не указано в настройках, то по умолчанию название поста,
* ***{$author}*** - если не указано в настройках, то по умолчанию имя автора в метаполе 'author' поста, если же и метаполе отсутствует, то название сайта

###  4. Параметры JS-скриптов

Параметры для JS-скрипта (в плагине задаются автоматически):

```html
<script>
	var bg_counter = {
		"counterurl":"https://stat.azbyka.ru/counters",	    // Всего просмотров счётчик и количество просматривающих в данный момент
		"rateurl":"https://stat.azbyka.ru/rate", 			// Отправляет оценку объекта на сервер и возвращает новый рейтинг и количество голосов
		"scoreurl":"https://stat.azbyka.ru/item-score",     // Возвращает рейтинг и количество голосов отдельно взятого объекта и флаг - голосовл ли IP или нет? 
		"websocket":"wss://stat.azbyka.ru/realtime-view",	// Сокет: пока соединение активно, оно будет засчитываться как один просмотр в текущий момент
		"updatesocket":"wss://stat.azbyka.ru/updates",      // Сокет: просмотр счетчиков online-посетителей
		"updatetime":"3000",                                // Периодичность обновления счетчиков online-посетителей
		"maxreconnect":"5", 							// Макс. количество повторов подключений
		"project":"/project/propovedi",					    // Имя текущего проекта
		"type":"post",								        // Пока только "post" или пусто для ID=""
		"ID":"1234",									    // ID поста или пусто, чтобы не собирать статистику
		"votes5":"голосов",
		"votes2":"голоcа", 
		"vote1":"голос",
		"voted":"Вы уже проголосовали",
		"price":["худо", "слабо", "сносно", "достойно", "чудесно"],

		"debug":""									// Выводить или нет инфу в консоль
	};
</script>
```

В настройках плагина можно задать периодичность обновления счетчиков online-посетителей в мсек.

###  5. Выводит на экран список популярных постов и рейтинга записей

Вывод списка популярных постов: шорт-код `[bg_counter_top_posts limit='10' number='true']` или php-функция `getPopularPosts ($limit, $number);`, 

где 

* ***limit*** - количество постов в списке, 
* ***number = true*** - нумерованный список, ***false*** - ненумерованный. 

В настройках плагина можно задать периодичность обновления списка популярных постов. 
Поскольку эта возможность реализована на серверной стороне WP необходимо согласовать это время с обновлением кеш страниц.

Формат вывода списка на экран:

```html
<ul class="bg-az-top-posts">
	<li><a href="https://azbyka.ru/.../?p=186" title="...">...</a> - <span class="bg-az-count">...</span></li>
	<li><a href="https://azbyka.ru/.../?p=263" title="...">...</a> - <span class="bg-az-count">...</span></li>
	...
</ul>
```

Вывод рейтинга записей: шорт-код `[bg_counter_post_rating limit='10' number='true']` или php-функция `getPostRating ($limit, $number);`, параметры те же.

Виджеты для боковой панели: **"Популярные записи"** и **"Рейтинг записей"**, который работает аналогично соответствующему шорт-коду. 

Виджет **"Сейчас на сайте"**, выводит в боковую панель количество онлайн-посетителей на сайте, общее количество просмотров страниц и количество записей.

Чтобы удалить лишние типы записей из подсчета количества записей используйте хук:

```php
add_filter('bg-az-counter-widget__post-types', 'remove_post_type');
function remove_post_type ($post_types) {
	unset( $post_types['page'] ); // удалим page
	...
	return $post_types;
}
```

`$post_types` - список зарегистрированных типов записей.

Если `$post_types` - пустой массив или количество записей равно нулю, то строка **"Всего записей:"** не отображается.

Количество онлайн-посетителей обновляется каждые 3 секунды. Остальные показатели не изменяются.

## Импорт данных, архивирование и валидация данных

Плагин позволяет загрузить на сервер данные из плагинов  **Post Views Counter**, **WP Popular Posts**, **Bg Az-Rating**, а также из произвольных полей **"views"**.

Плагин ежедневно сохраняет архив данных в файлах `wp-content/uploads/bg_az_counter.json` и `wp-content/uploads/bg_az_rating.json`, которые при необходимости можно также загрузить на сервер.

Ошибки фиксируются в логе плагина: `wp-content/plugins/bg-az-counter/bg_counter.log`.

Чтобы удалить лишние записи с сервера достаточно обнулить значения счетчиков. 

Проверка на валидность при сохранении архива:
 
* имеет ли запись с данным **ID** тип *'post'*, *'page'* или *пользовательский тип* и статус - *'publish'*. 

Все некорректные записи обнуляются и будут удалены на сервере.

**Внимание!** После удаления плагина файлы `wp-content/uploads/bg_az_counter.json` и `wp-content/uploads/bg_az_rating.json` **НЕ будут удалены**! При необходимости, удалите их вручную.


