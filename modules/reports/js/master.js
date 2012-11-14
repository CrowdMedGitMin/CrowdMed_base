(function($){
	IS = window.IS || {};
	IS.grapher = {
		chart: null,
		init: function(elem,type) {
			IS.grapher.chart = $('<table />', { 'id': elem.substr(1) + '-data', 'class': 'hidden' });
			IS.grapher.getData(elem.substr(7),type);
			$(elem).append(IS.grapher.chart);
			IS.grapher.chart.visualize({ 
				type: 'line', 
				width: '600px', 
				height: '500px', 
				lineWeight: 3,
				yLabelInterval: 40
			});
		},
		getData: function(market,type) {
			var data = {
				action: 'getData',
				m: market,
				u: 'MASTER'
			};
			
			if (type) {
				data['id'] = 1;
			}
			
			$.ajax({
				type: "POST",
				url: "../../rd2/actions.php",
				dataType: "json",
				data: data,
				async: false,
				success: function(data) {
					IS.grapher.parse(data);
				}
			});
		},
		parse: function(data) {
			var headerRow = $('<tr />').appendTo(IS.grapher.chart);
			$('<td />').appendTo(headerRow);
			
			$.each(data.times, function(time,item){
				$('<th />', { html: time }).appendTo(headerRow);
			});
			
			$.each(data.vwap, function(id,concept){
				var dataRow = $('<tr />');
				$('<th />', { html: data.concepts[id] }).appendTo(dataRow);
				$.each(concept, function(i,value){
					$('<td />', { html: value }).appendTo(dataRow);
				});
				dataRow.appendTo(IS.grapher.chart);
			});
		}
	};
})(jQuery);