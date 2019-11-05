var bg_counter_elements = 0;

jQuery( document ).ready(function() {

	if (bg_counter.ID) {
		SendOnce(bg_counter.type, bg_counter.ID);
		var request = bg_counter.websocket+bg_counter.project+"/"+bg_counter.type+"/"+bg_counter.ID;
	// Используется библиотека ReconnectingWebSocket (https://github.com/joewalnes/reconnecting-websocket)
	// для переподключения сокета при разрыве соединения. 
	// Вместо: 	var socket = new WebSocket(request);
	// используем вызов:
//		var socket = new ReconnectingWebSocket(request);
		var socket = new ReconnectingWebSocket(request, null, { timeoutInterval: 10000 });
		socket.onopen = function() {
			if (bg_counter.debug) console.log(" Соединение установлено: "+request);
			GetAllCounters();
		};
		// Обработка ошибок
		if (bg_counter.debug) {
			socket.onerror = function(error) {
				console.log("Ошибка " + error.message);
			};
			socket.onclose = function(event) {
				if (event.wasClean) {
					console.log('Соединение закрыто чисто: '+request);
				} else {
					console.log('Обрыв соединения: '+request);
				}
				console.log('Код: ' + event.code + ' причина: ' + event.reason);
			};
		}
	} else GetAllCounters();
	
	//	Обновляет счетчики после прокрутки страницы, если добавлены элементы.
	jQuery(window).on('scroll', function() {
		GetAllCounters();
	});

/*********************************************************************************
	Просомтр счетчиков читающих в реальном времени.

	Подключаемся к /updates/ после подключения webscoket будет ожидать 
	массив из путей счётчиков:

	["/project/test", "/project/test/author/1"]
	Теперь webscoket каждые 3 секунды будет присылать обновления по этим счетчиками
	в формате:

	{
	  "/project:test": 12,
	  "/project:test/author:1": 6
	}
	Поменять список отслеживаемых счетчиков можно повторно отправив массив и путей.
**********************************************************************************/
	// Массив из путей счётчиков
	var data = new Array();
	var i = 0;
	jQuery('span.bg-az-counter').each (function () {
		var el = jQuery(this);
		var project = el.attr('data-project');
		if (project == "") path = "/";
		else {
			if (project == undefined) project = bg_counter.project;
			else project = '/project/'+project;
			var type = el.attr('data-type');
			var id = el.attr('data-ID');
			if (!type || !id) var path = project;
			else var path = project+"/"+type+"/"+id;
		}
		data[i] = path;
		i++;
	});
	if (data.length) {
		var json = JSON.stringify(data);
		var request = bg_counter.updatesocket+(bg_counter.updatetime?('?time='+bg_counter.updatetime):'');
		// Создаем сокет
//		var updatesocket = new ReconnectingWebSocket(request);
		var updatesocket = new ReconnectingWebSocket(request, null, { timeoutInterval: 10000 });
		// Отправляем данные, как только сокет будет подключен
		updatesocket.onopen = function() {
			if (bg_counter.debug) {
				console.log(" Соединение установлено: "+request);
				console.log(" Path ("+i+"): "+json);
			}
			updatesocket.send(json);
		};
		// Слушаем сокет
		updatesocket.onmessage   = function(e) {
			if (bg_counter.debug) console.log(" Пришло сообщение: "+e.data);
			var online = JSON.parse(e.data);
			jQuery('span.bg-az-counter').each (function () {
				var el = jQuery(this);
				var type = el.attr('data-type');
				var id = el.attr('data-ID');
				var project = el.attr('data-project');
				if (project == "") path = "/";
				else {
					if (project == undefined) project = bg_counter.project;
					else project = '/project/'+project;
					if (!type || !id) var path = project;
					else var path = project+"/"+type+"/"+id;
				}
				for (var key in online) {
					if(path == key) {
						el.find('span.bg-az-counter-now').text(addDelimiter(online[key]));
					}
				}
			});
		};
		// Обработка ошибок
		if (bg_counter.debug) {
			updatesocket.onerror = function(error) {
				console.log("Ошибка " + error.message);
			};
			updatesocket.onclose = function(event) {
				if (event.wasClean) {
					console.log('Соединение закрыто чисто: '+request);
				} else {
					console.log('Обрыв соединения: '+request); 
				}
				console.log('Код: ' + event.code + ' причина: ' + event.reason);
			};
		}
	}
});
/*********************************************************************************
	POST /counters/<path>


	Увеличивает счётчик на единицу (и создаёт его, если он не существует).
	Тело запроса пустое.

	Пример запроса:

	POST /counters/project/test/author/1/book/3

	Пример ответа:

	{
	  "success":true,
	  "data":{
		"created": false,
		"now": 3,
		"value": 35
	  }
	}
	В ответе параметр created говорит, существовал ли счётчик до этого.
**********************************************************************************/
function SendOnce(type, id) {
	
	var request = bg_counter.counterurl+bg_counter.project+"/"+type+"/"+id;
	var xhr = new XMLHttpRequest();
	xhr.open("POST", request, true);
	if (bg_counter.debug) console.log('POST REQUEST: '+request);
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && xhr.status == 200) {
			if (xhr.responseText) {
				var response =  JSON.parse(xhr.responseText);
				if (response.success) {
					// Здесь надо будет добавить вывод данных на экран
					if (bg_counter.debug) console.log(JSON.stringify(response.data));
					setViewCount (type, id, bg_counter_number_format(response.data.value), addDelimiter(response.data.now+1));
				} else {
					if (bg_counter.debug) console.log('POST REQUEST: '+request+' ERROR: '+response.error);
				}
			}
		}
	}
	xhr.send();

}

/*********************************************************************************
GET /counters/<path>

Возвращает текущие значения счётчика - общий счётчик и количество
просматривающих в данный момент.

Пример запроса:

GET /counters/project/test/author/1/book/3

Пример ответа:

{
  "success":true,
  "data":{
    "now":3,
    "total":34
  }
}
Если счётчик не существует, возвращает 404.
**********************************************************************************/
function GetAllCounters() {
	
	var elem  = jQuery('span.bg-az-counter');

	if( typeof elem == 'undefined' ) {
		return;
	}
	if (elem.length > bg_counter_elements) {
		bg_counter_elements = elem.length;
		jQuery('span.bg-az-counter').each (function () {
			var el = jQuery(this);
	//		bg_counter_elements = el.length;
			var type = el.attr('data-type');
			var id = el.attr('data-ID');
			var project = el.attr('data-project');
			if (project == "") request = bg_counter.counterurl;
			else {
				if (project) project = '/project/'+project;
				else project = bg_counter.project;
				
				if (!type || !id) var request = bg_counter.counterurl+project;
				else var request = bg_counter.counterurl+project+"/"+type+"/"+id;
				
			}
			var xhr = new XMLHttpRequest();
			xhr.open("GET", request, true);
			xhr.onreadystatechange = function() {
				if (xhr.readyState == 4 && xhr.status == 200) {
					if (xhr.responseText) {
						var response =  JSON.parse(xhr.responseText);
						if (response.success) {
							if (bg_counter.debug) console.log('GET REQUEST: '+request);
							if (bg_counter.debug) console.log(JSON.stringify(response.data)); 
							el.find('span.bg-az-counter-views').text(bg_counter_number_format(response.data.total));
							el.find('span.bg-az-counter-now').text(addDelimiter(response.data.now));
						} else {
							if (bg_counter.debug) console.log('GET REQUEST: '+request+' ERROR '+xhr.status+': '+response.error);
							el.find('span.bg-az-counter-views').text('0');
							el.find('span.bg-az-counter-now').text('0');
						}
					} else {
						if (bg_counter.debug) console.warn('GET REQUEST: '+request+' Warning: responseText is empty!');
						el.find('span.bg-az-counter-views').text(' - ');
						el.find('span.bg-az-counter-now').text(' - ');
					}
				}
			}
			xhr.onerror = function(err) {
				console.warn(err.type +" "+ err.target.status + ". Check if the server is running!");  
				el.find('span.bg-az-counter-views').text(' - ');
				el.find('span.bg-az-counter-now').text(' - ');
			}
			xhr.send();
		});
	}
}
/*********************************************************************************
	Отображает значения счетчика на странице

**********************************************************************************/
function setViewCount (type, id, total='', now='') {
	jQuery('span.bg-az-counter').each (function () {
		var el = jQuery(this);
		if(id == el.attr('data-ID') && type == el.attr('data-type')) {
			if (total) el.find('span.bg-az-counter-views').text(total);
			if (now) el.find('span.bg-az-counter-now').text(now);
		}
	});
}

// Добавляет разделитель тысяч
function addDelimiter(nStr, dlm='\xa0') {
    if (!nStr) nStr = '0';
	nStr += '';
    var x = nStr.split('.');
    var x1 = x[0];
    var x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + dlm + '$2');
    }
    return x1 + x2;
}
function bg_counter_number_format (num) {
	num = parseFloat (num);
	if (num > 1000000000.0) {
		num = num/1000000000;
		num = num.toFixed(1)+"\xa0млрд.";
	} else if (num > 1000000.0) {
		num = num/1000000;
		num = num.toFixed(1)+"\xa0млн.";
	} else if (num > 10000.0) {
		num = num/1000;
		num = num.toFixed(1)+"\xa0тыс.";
	} else {
		num = addDelimiter(num);
	}
	return num;
}

