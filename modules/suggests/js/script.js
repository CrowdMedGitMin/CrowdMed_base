(function($){
$.fn.suggestions = function(options){
	return $(this).each(function(){
		var o = $.extend({
			ul: $('ul.suggestions', this),
			market: 0,
			concept: 1,
			user: 'debug',
			source: 'https://www.infosurv.com/@live/ice/suggests/ajax.php',
			type: 'jsonp'
		},options);
		
		var suggestions = {
			getData: function(){
				$.ajax({
					url: o.source,
					dataType: o.type,
					data: { action:'grab', market: o.market, concept: o.concept, user: o.user },
					success: function(data){
						o.ul.append(data);
					}
				});
			},
			submit: function(){
				var submit		= $(this),
					textField	= $('input.suggestionText',$(this).parent());

				// Preventing double submits:
				if(submit.hasClass('working') || textField.val().length<3){
					return false;
				}

				submit.addClass('working');

				$.ajax({
					url: o.source,
					dataType: o.type,
					data: { action:'submit', content:textField.val(), market: o.market, concept: o.concept },
					success: function(msg){
						textField.val('');
						submit.removeClass('working');

						if(msg.html){
							// Appending the markup of the newly created LI to the page:
							$(msg.html).hide().appendTo(o.ul).slideDown();
						}
					}
				});

				return false;
			},
			vote: function(){
				console.log('vote event');
				var elem		= $(this),
					parent		= elem.parent(),
					li			= elem.closest('li'),
					ratingDiv	= li.find('.rating'),
					id			= li.attr('id').replace('s',''),
					v			= 1;

				// If the user's already voted:

				if(parent.hasClass('inactive')){
					return false;
				}

				parent.removeClass('active').addClass('inactive');

				if(elem.hasClass('down')){
					v = -1;
				}

				// Incrementing the counter on the right:
				//ratingDiv.text(v + +ratingDiv.text());

				// Turning all the LI elements into an array
				// and sorting it on the number of votes:
/*
				var arr = $.makeArray(o.ul.find('li')).sort(function(l,r){
					return +$('.rating',r).text() - +$('.rating',l).text();
				});
*/
				// Adding the sorted LIs to the UL
				//o.ul.html(arr);

				// Sending an AJAX request
				$.ajax({
					url: o.source,
					dataType: o.type,
					data: { action:'vote', vote:v, 'id':id, market: o.market, concept: o.concept, user: o.user }
				});
				return true;
			},
			init: function(){
				suggestions.getData();
			}
		};
		suggestions.init();
		$('div.vote span',this).live('click',suggestions.vote);
		$('input.submitSuggestion',this).click(suggestions.submit);
		$('input.suggestionText',this).keydown(function(e){
			if(e.keyCode == 13){
				e.preventDefault();
				suggestions.submit;
			}
		});
	});
};
})(jQuery);