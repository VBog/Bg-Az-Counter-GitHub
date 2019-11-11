var bg_counter_elements = 0;
var notconnected = 0;

jQuery( document ).ready(function() {

/*********************************************************************************

	Если задан ID, то увеличиваем счетчик посетителей и включаем сокет присутствия
	
**********************************************************************************/

	if (bg_counter.ID) {
		SendOnce(bg_counter.type, bg_counter.ID);
		var request = bg_counter.websocket+bg_counter.project+"/"+bg_counter.type+"/"+bg_counter.ID;
	// Используется библиотека ReconnectingWebSocket (https://github.com/joewalnes/reconnecting-websocket)
	// для переподключения сокета при разрыве соединения. 
	// Вместо: 	var socket = new WebSocket(request);
	// используем вызов:
		var socket = new ReconnectingWebSocket(request, null, { debug: bg_counter.debug, timeoutInterval: 10000, reconnectInterval: 5000, maxReconnectAttempts: 20 });
		socket.onopen = function() {
			if (bg_counter.debug) console.log(" Соединение установлено: "+request);
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
	}

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
	var request = bg_counter.updatesocket+(bg_counter.updatetime?('?time='+bg_counter.updatetime):'');
	// Создаем сокет
	var updatesocket = new ReconnectingWebSocket(request, null, { debug: bg_counter.debug, timeoutInterval: 10000, reconnectInterval: 5000, maxReconnectAttempts: 20 });
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
	updatesocket.onerror = function(error) {
		if (bg_counter.debug) console.log("Ошибка " + error.message);
	};
	updatesocket.onclose = function(event) {
		if (event.wasClean) {
			if (bg_counter.debug) console.log('Соединение закрыто чисто: '+request);
		} else {
			if (bg_counter.debug) console.log('Обрыв соединения: '+request); 
		}
		if (bg_counter.debug) console.log('Код: ' + event.code + ' причина: ' + event.reason);
	};

	
/*********************************************************************************

	Каждые 3 сек. проверяем не добавлены ли счетчики, 
	если добавлены, то запрашиваем данные.
	
**********************************************************************************/
	
		if (fullBatchQuery(updatesocket)) {
			let timerAllCountersId = setTimeout(function tickAllCounters() {
				if (fullBatchQuery(updatesocket)) {
					timerAllCountersId = setTimeout(tickAllCounters, bg_counter.updatetime?bg_counter.updatetime:3000); 
				}
			}, bg_counter.updatetime?bg_counter.updatetime:3000);
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
	
	jQuery.ajax ({
		url: request,
		type: "POST",
		success: function(response){
			if (response.success) {
				// Здесь надо будет добавить вывод данных на экран
				if (bg_counter.debug) {
					console.log('POST REQUEST: '+request+' result:');
					console.log(JSON.stringify(response.data));
				}
				setViewCount (type, id, bg_counter_number_format(response.data.value), addDelimiter(response.data.now+1));
			}
		},
		error: function(xhr) {
			if (bg_counter.debug) console.warn('POST REQUEST: '+request+' ERROR '+xhr.status+': '+xhr.statusText);
		}
	});
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
// Сокращает запись числа до млрд., млн., тыс.
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

/*********************************************************************************
POST /batch-query

Возвращает счётчики просмотров, рейтинги и онлайн-счётчики для массива путей.

Пример запроса:
POST /batch-query

Тело запроса:

["/project:test", "/some:path"]
Пример ответа:

{
  "/project:test": {
    "viewCounter": 5,
    "onlineCounter": 0,
    "rating": null
  },
  "/some:path": {
    "viewCounter": 4,
    "onlineCounter": 2,
    "rating": 3.8
  }
}
**********************************************************************************/

function fullBatchQuery(socket) {
	
	// Массив из путей счётчиков
	var elem  = jQuery('span.bg-az-counter');
	if( typeof elem == 'undefined' ) return false;					// Нет полей для вывода информации на странице
	if (notconnected > bg_counter.maxreconnect) return false;		// Не более maxReConnect сбоев при запросе

	if (elem.length > bg_counter_elements) {						// Если количество счетчиков на странице больше уже учтенных
		var data_added = new Array();
		var data = new Array();
		var i = 0;
		var elem_num = 0;
		// Для каждого счетчика
		elem.each (function () {
			var el = jQuery(this);
			var project = el.attr('data-project');
			if (project == "") path = "/";							// Формируем путь
			else {
				if (project == undefined) project = bg_counter.project;
				else project = '/project/'+project;
				var type = el.attr('data-type');
				var id = el.attr('data-ID');
				if (!type || !id) var path = project;
				else var path = project+"/"+type+"/"+id;
			}
			if (elem_num >= bg_counter_elements) {					// Только новые данные
				data_added[i] = path; 
				i++;
			}
			data[elem_num] = path;									// Все данные
			elem_num++;
		});
		bg_counter_elements = elem.length;							// Учитены все счетчики на страниц
		
		if (data_added.length) {									// Если есть добавленые счетчики
			var request = bg_counter.batch;
			var json_added = JSON.stringify(data_added);
			var json = JSON.stringify(data);
			if (bg_counter.debug) {
				console.log(" Запрос: "+request);
				console.log(" Path ("+i+"): "+json);
			}
	
			// Пакетный запрос batch-query
			jQuery.ajax ({
				url: request,
				type: "POST",
				data: json_added,
				success: function(data){
					// Здесь надо будет добавить вывод данных на экран
					if (bg_counter.debug) {
						console.log('POST REQUEST: '+request+' result:');
						console.log(JSON.stringify(data));
					}
					// Для каждого счетчика
					jQuery('span.bg-az-counter').each (function () {
						var el = jQuery(this);
						var type = el.attr('data-type');
						var id = el.attr('data-ID');
						var project = el.attr('data-project');
						if (project == "") path = "/";				// Формируем путь
						else {
							if (project == undefined) project = bg_counter.project.replace('/project/','/project:');
							else project = '/project:'+project;
							if (!type || !id) var path = project;
							else var path = project+"/"+type+":"+id;
						}
						for (var key in data) {
							if(path == key) {						// Сравниваем путь с ключем присланных данных
								// Количество посещений
								if (data[key].viewCounter) 
									el.find('span.bg-az-counter-views').text(bg_counter_number_format(data[key].viewCounter));
								else
									el.find('span.bg-az-counter-views').text(0);
								// Количество online-посетителей
								el.find('span.bg-az-counter-now').text(addDelimiter(data[key].onlineCounter));
								// Райтинг
								if (data[key].rating) 
									el.find('span.bg-az-counter-score').text(parseFloat(data[key].rating.score).toFixed(1));
								else 
									el.find('span.bg-az-counter-score').text('-');
							}
						}
					});
				},
				error: function(xhr) {
					if (bg_counter.debug) console.warn('POST REQUEST: '+request+' ERROR '+xhr.status+': '+xhr.statusText);
					notconnected++;			// Количество повторов запросов
				}
			});

			if (socket !== undefined) {
				// Отправляем данные, как только сокет будет подключен
				if (socket.readyState == WebSocket.OPEN) {
					socket.send(json);
				} else {
					socket.onopen = function() {
						socket.send(json);
					}
				}
			}
		}
	}
	return true;
}
