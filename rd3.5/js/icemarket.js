(function($){
	function getUrlVars()	{
	    var vars = [], hash;
	    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
	    for(var i = 0; i < hashes.length; i++) {
	        hash = hashes[i].split('=');
	        vars.push(hash[0]);
	        vars[hash[0]] = hash[1];
	    }
	    return vars;
	}
	
	
	parent_url = parent_url || 'https://www.feedback.infosurv.com/se.ashx';
	
	$('#mainTable').find('tbody:first > tr:even').addClass('grayBar');
	
	$('#getStock')
		.button({ disabled: true })
		.show()
		.click(function(){
			var button = $(this);
			if(!button.hasClass('ui-state-disabled')){
				button.button('option','disabled',true);
				var postData = '';
				$('div.slider').each(function(){
					var stock = $(this);
					postData += '&stk' + stock.attr('id').substr(1) + '=' + stock.slider('option','value');
				});
				$.ajax({
					type: 'POST',
					url: 'actions.php',
					data: 'action=getStock&m=' + marketID + '&u=' + userID + '&c=' + culture + postData,
					dataType: 'json',
					success: function(){
						submitData();
					},
					error: function(){
						// Error while purchasing stock
						$('#error')
							.dialog('option','title',language.loading)
							.html(language.errormsg + '<button id="reSubmit">' + language.retry + '</button>')
							.dialog('open')
							.parent()
								.find('.ui-dialog-titlebar-close')
									.remove();
						$('#reSubmit').click(function(){
							$('#error').dialog('close');
							button.button('option','disabled',false);
							$('#getStock').trigger('click');
							return false;
						});
					}
				});
			}
			return false;
		});



	if(colors && colors != ""){
		colors = colors.split(",");
	} else {
		colors = null;
	}

	var pieColors = colors || ['#be1e2d','#666699','#92d5ea','#ee8310','#8d10ee','#5a3b16','#26a4ed','#f45a90','#e9e744','#54e944','#cc44e9','#9d1c1c','#7fa9dd','#b4dd7f','#a48d0e','#2861af'];
	
	
	$('#graph4 table')
		.visualize({
			type: 'pie',
			height: '300px',
			width: '400px',
			pieMargin: 10,
			title: language.whatothers,
			colors: pieColors
		})
		.trigger('visualizeRefresh');
	$('#graph5 table')
		.visualize({
			type: 'pie',
			height: '300px',
			width: '400px',
			pieMargin: 10,
			title: language.whati,
			pieRemainder: $('td',$('#graph5 table')).length,
			colors: pieColors
		})
		.trigger('visualizeRefresh');
	
	var submitData = function(){
		var conceptObj = {};
		$('div.slider').each(function(){
			var stock = $(this);
			conceptObj[stock.attr('name')] = stock.slider('option','value');
		});
		$.postMessage({ 
			concepts: conceptObj
		}, parent_url, window.parent );
	};
	// Create sliders for each concept
	$('div.slider').slider({
		value:0,
		range: 'min',
		min: 0,
		max: 100,
		step: 1,
		slide: function(event, ui) {
			//var $this = $(this);
			var textsize = (ui.value == 100) ? '.5' : '.7';
			$('a.ui-slider-handle', this)
				.text(ui.value)
				.css('font-size',textsize + 'em');
		},
		stop: function(event,ui){
			var $this = $(this),
				stockID = $this.attr('id').substr(1),
				total = 0,
				sliders = $('div.slider'),
				counter = 0;
			sliders.not($this).each(function(){
				total += $(this).slider('option','value');
			});
			if(ui.value > (100-total)){
				$this.slider('option','value',100-total);
				ui.value = 100-total;
			}
			var textsize = (ui.value == 100) ? '.5' : '.7';
			$('a.ui-slider-handle', this)
				.text(ui.value)
				.css('font-size',textsize + 'em');
			// method to get amount of stock
			var postData = '';
			sliders.each(function(){
				counter += $(this).slider('option','value');
				postData += '&stk' + $(this).attr('id').substr(1) + '=' + $(this).slider('option','value');
			});
			// Enable buy button if total is 100%
			if(counter == 100){
				$('#getStock').button('option','disabled',false);
			} else $('#getStock').button('option','disabled',true);
			$.ajax({
				url: 'actions.php',
				type: 'POST',
				data: 'action=getAmount&m=' + marketID + '&u=' + userID + '&c=' + culture + postData,
				dataType: 'json',
				success: function(data){
					$.each(data, function(i,item){
						var amount = "" + (item * parseFloat(language.exchange)).toFixed(2),
							dollars = amount.split('.'), result;
						if (wholes) {
							result = language.currency + dollars[0];
						} else {
							result = language.currency + dollars[0] + language.currencyDelimeter + dollars[1];
						}
						$('#A' + i).html(result);
					});
				}
			});
			$('#graphKey'+stockID)
				.text((ui.value==0)?0.001:ui.value); /*IEbug: Values in the data table cannot be 0, so i force them to be .001*/
			$('#graphKeyBase')
				.text((100-(ui.value+total) == 0)?0.001:(100-(ui.value+total))); /*IEbug: Values in the data table cannot be 0, so i force them to be .001*/
			$('.visualize').trigger('visualizeRefresh');
		}
	})
		.each(function(){
			var stockid = $(this).attr('name');
			$(this)
				.removeClass('ui-corner-all')
				.find('.ui-slider-range')
					.css('background',pieColors[stockid])
				.end()
				.find('.ui-slider-handle')
					.text('0');
		});
	$('#error').dialog({
		bgiframe: true,
		width: 500,
		modal: true,
		resizable: false,
		draggable: false,
		autoOpen: false,
		buttons: {
			Ok: function() {
				$(this).dialog('close').dialog('option','title','').dialog('option','width',500).empty();
			}
		}
	});
	
	var sliders = getUrlVars()
	$.each(sliders, function (i, slideid) {
		if (slideid.substr(0,1) === "S") {
			$('#'+slideid).slider("value", sliders[slideid]);
		}
	});
	
	var concepts = {
		init: function(){
			var self = this;
			$.ajax({
				type: 'POST',
				url: 'actions.php',
				data: 'action=getConcepts&u='+userID+'&m='+marketID+'&id=1&c=' + culture,
				dataType: 'json',
				success: function(data) {
					self.parsedata(data);
				}
			});
		},
		parsedata: function(data){
			var self = this;
			$.each(data, function(i,item){
				var elm = $('#D'+item.cid),
					parElm = elm.parent(),
					tip = $('<div />',{
						'class': 'conceptHolder tooltip ui-widget ui-widget-content',
						'id': 'concept'+item.cid
					})
						.append(item.content)
						.append('<div class="fg-tooltip-pointer-left ui-widget-content"><div class="fg-tooltip-pointer-left-inner"></div></div>')
						.appendTo(parElm);
				elm.hover(function(event){
					self.show(event,$('#concept'+item.cid),this);
				});
			});
		},
		show: function(event,tip,target){
			tip
				.css({
					'top':0,
					'left':0
				})
				.show()
				.position({
					my: 'left bottom',
					at: 'right center',
					of: tip.parent(),
					collision: 'fit',
					offset: '18 30'
				})
				.hide();
			if(event.type == 'mouseenter'){
				tip.show();
			} else {
				tip.hide();
			}
		}
	};
	concepts.init();

	var tooltips = {
		tips: $('div.tooltip'),
		triggers: $('.mouseeffects'),
		init: function(){
			var self = this;
			self.tips.each(function(){
				var tip = $(this);
				tip
					.addClass('ui-widget ui-widget-content')
					.append('<div class="fg-tooltip-pointer-down ui-widget-content"><div class="fg-tooltip-pointer-down-inner"></div></div>');
				tip.parent().hover(
					function(event){
						self.show(event,tip,this);
					});
			});
		},
		show: function(event,tip,target){
			tip
				.css({
					'top':0,
					'left':0
				})
				.show()
				.position({
					my: 'center bottom',
					at: 'center top',
					of: target,
					offset: '0 -25',
					collision: 'fit'
				})
				.hide();
			if(event.type == 'mouseenter'){
				tip.fadeIn(500);
			} else {
				tip.fadeOut(100);
			}
		}
	};
	tooltips.init();
	
	if(ice_purchace == '1'){
		submitData();
	}
	$(document)
		.bind('contextmenu',function(){
			alert(language.whatothers);
			return false;
		})
		.bind('keyup',function(e){
			if(e.keyCode == 44) alert(language.printscreen);
			return false;
		})
		.ready(function(){
			$('.visualize').trigger('visualizeRefresh');
			$.postMessage({ if_height: $('body').outerHeight( true )  }, parent_url, window.parent );
		});
})(jQuery);