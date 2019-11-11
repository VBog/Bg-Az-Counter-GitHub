var start_rate_index, rating_voted;

jQuery( document ).ready(function() {
	
	if (!bg_counter.ID) return;		// У объекта нет ID
	if (jQuery("div").is(".bg_counter_rating") == false) return;	// На странице нет счетчика

	start_rate_index =  parseFloat(jQuery( "#bg_counter_score" ).html());
	rating_voted = (jQuery( "#bg_counter_score" ).attr("data-voted")=='true')?true:false;
	iniRatingState(start_rate_index, rating_voted);

	getRate(bg_counter.type, bg_counter.ID);

	jQuery( "#bg_counter_rate_box li" ).mouseover(function() {
		if(!rating_voted){
			var index = jQuery( this ).index();
			iniRatingState(index+1, rating_voted);
			jQuery('#bg_counter_popup_help').text(bg_counter.price[index]);
		} else {
			jQuery('#bg_counter_popup_help').text(bg_counter.voted);
		}
		jQuery('#bg_counter_popup_help').show();
	});

	jQuery( "#bg_counter_rate_box" ).mouseout(function() {
		if(!rating_voted){
			iniRatingState(start_rate_index, rating_voted);	
		}
		jQuery('#bg_counter_popup_help').hide();
	});
	jQuery( "#bg_counter_rate_box li" ).click(function() {
		if(!rating_voted){
			rating_voted = true;
			jQuery( "#bg_counter_rate_box li" ).css('cursor', 'default');
			var sindex = jQuery( this ).index()+1;
			sendRate(bg_counter.type, bg_counter.ID, sindex);
		}
	});
});
function iniRatingState(sindex, voted){
	if(!voted) jQuery( "#bg_counter_rate_box li" ).css('cursor', 'pointer');	
	else jQuery( "#bg_counter_rate_box li" ).css('cursor', 'default');	
	star = parseInt(jQuery( "#bg_counter_rate_box li" ).css('height')); /* высота звездочки */
	jQuery( "#bg_counter_rate_box li" ).css('background-position', '0px '+star+'px');
	jQuery( "#bg_counter_rate_box li" ).each(function( index ) {
		n=sindex-sindex%1;
		if(index < n){
			jQuery(this).css('background-position', '0px '+5*star+'px');
		}
		else if (sindex-index > 0) {
			p=star*(Math.round(4*(sindex-index))+1);
			jQuery(this).css('background-position', '0px '+p+'px');
		}
	});
}
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
function getRate(type, id) {
	
	var request = bg_counter.scoreurl+bg_counter.project+"/"+type+"/"+id;

	jQuery.ajax ({
		url: request,
		type: "GET",
		success: function(response){
			if (response.success) {
				if (bg_counter.debug) {
					console.log('POST REQUEST: '+request+' result:');
					console.log(JSON.stringify(response));
				}
				m = response.data.votes % 10; 
				j = response.data.votes % 100;
				if(m==0 || m>=5 || (j>=10 && j<=20)) txt_votes = bg_counter.votes5;
				else if(m>=2 && m<=4) txt_votes = bg_counter.votes2; 
				else txt_votes = bg_counter.vote1;
				start_rate_index = parseFloat(response.data.score).toFixed(1);
				jQuery('#bg_counter_votes').html(response.data.votes);
				jQuery('#bg_counter_votes_txt').html(txt_votes);
				jQuery('#bg_counter_score').html(start_rate_index);
				jQuery('meta[itemprop=ratingCount]').attr("content", response.data.votes);
				jQuery('meta[itemprop=ratingValue]').attr("content", start_rate_index);
				rating_voted = response.data.alreadyVoted;
				iniRatingState(start_rate_index, rating_voted);	
			} else {
				jQuery('#bg_counter_votes').html('0');
				jQuery('#bg_counter_votes_txt').html(bg_counter.votes5);
				jQuery('#bg_counter_score').html('0');
				jQuery('meta[itemprop=ratingCount]').attr("content", 0);
				jQuery('meta[itemprop=ratingValue]').attr("content", 0);
				iniRatingState(0, false);	
			}
		},
		error: function(xhr) {
			if (bg_counter.debug) console.warn('POST REQUEST: '+request+' ERROR '+xhr.status+': '+xhr.statusText);
		}
	});
}
/*********************************************************************************
POST /rate/<path>


Увеличивает сумму оценок объекта на указанную величину (от 1 до 5) и количество 
голосов на 1. 
Возвращает новый рейтинг и количество голосов. Рейтинг рассчитывается по формуле:
новый рейтинг = сумма оценок / количество голосов;

Пример запроса:

POST /rate/project/test/author/1/book/3

Тело: {"rating": 4}

Пример ответа:

{
  "success": true, 
  "data": {"score": 4.0625, "votes": 16}
}

**********************************************************************************/
function sendRate(type, id, number) {
	
	var request = bg_counter.rateurl+bg_counter.project+"/"+type+"/"+id;

	jQuery.ajax ({
		url: request,
		type: "POST",
		data: '{"rating": '+number+'}',
		success: function(response){
			if (response.success) {
				// Вывод данных на экран
				if (bg_counter.debug) {
					console.log('POST REQUEST: '+request+' result:');
					console.log(JSON.stringify(response));
				}
				m = response.data.votes % 10; 
				j = response.data.votes % 100;
				if(m==0 || m>=5 || (j>=10 && j<=20)) txt_votes = bg_counter.votes5;
				else if(m>=2 && m<=4) txt_votes = bg_counter.votes2; 
				else txt_votes = bg_counter.vote1;
				start_rate_index = parseFloat(response.data.score).toFixed(1);
				jQuery('#bg_counter_votes').html(response.data.votes);
				jQuery('#bg_counter_votes_txt').html(txt_votes);
				jQuery('#bg_counter_score').html(start_rate_index);
				jQuery('meta[itemprop=ratingCount]').attr("content", response.data.votes);
				jQuery('meta[itemprop=ratingValue]').attr("content", start_rate_index);
				iniRatingState(start_rate_index, true);
				
				jQuery('span.bg-az-counter').each (function () {
					var el = jQuery(this);
					var type = el.attr('data-type');
					var id = el.attr('data-ID');
					var project = el.attr('data-project');
					if (project == "") path = "/";				// Формируем путь
					else {
						if (project == undefined) project = bg_counter.project;
						else project = '/project/'+project;
						if (!type || !id) var path = project;
						else var path = project+"/"+type+"/"+id;
					}
					if (request == bg_counter.rateurl+path) el.find('span.bg-az-counter-score').text(parseFloat(response.data.score).toFixed(1));
				});
//				fullBatchQuery();
			}
		},
		error: function(xhr) {
			if (bg_counter.debug) console.warn('POST REQUEST: '+request+' ERROR '+xhr.status+': '+xhr.statusText);
		}
	});
}
