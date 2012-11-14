/*	
	jQuery pub/sub plugin by Peter Higgins (dante@dojotoolkit.org)
	Loosely based on Dojo publish/subscribe API, limited in scope. Rewritten blindly.
	Original is (c) Dojo Foundation 2004-2010. Released under either AFL or new BSD, see:
	http://dojofoundation.org/license for more information.
*/
(function(d){var b={};d.publish=function(a,c){b[a]&&d.each(b[a],function(){this.apply(d,c||[])})};d.subscribe=function(a,c){b[a]||(b[a]=[]);b[a].push(c);return[a,c]};d.unsubscribe=function(a){var c=a[0];b[c]&&d.each(b[c],function(e){this==a[1]&&b[c].splice(e,1)})}})(jQuery);
/*
 * Infosurv Concept Exchange - Suggestion Box
 * idea taken from http://tutorialzine.com/2010/08/ajax-suggest-vote-jquery-php-mysql/ and heavily modified
 */
(function($, undefined) {
	$.widget( "ICE.suggestions", {
		options: {
			market: 0,
			concept: 1,
			user: 'debug',
			source: '//presentation.infosurv.com/ice/modules/suggests/ajax.php',
			type: 'jsonp',
			translations: ['Thank you for submitting an idea.'],
			culture: 'en-US'
		},
		_create: function () {
			var self = this,
				o = this.options,
				elem = this.element;
			
			self._getData();
			
			$('input.submitSuggestion', $(elem).parent())
				.bind('click', function(event) {
					event.preventDefault();
					self._addSuggestion();
				});
			
			$('div.vote span', elem)
				.live('click', function (){
					self._vote(this);
				});
		},
		destroy: function () {
			$.Widget.prototype.destroy.apply( this, arguments );
		},
		_ajax: function (data, successfn) {
			var o = this.options;
			$.ajax({
				url: o.source,
				dataType: o.type,
				data: data,
				success: successfn
			});
		},
		_getData: function () {
			var elem = this.element,
				o = this.options;
			
			this._ajax({ action: 'grab', market: o.market, concept: o.concept, user: o.user, culture: o.culture }, function (data) {
				elem.append( data );
				$.publish('/suggests/grabbed', [ o.concept ]);
			});
		},
		_addSuggestion: function () {
			var self = this,
				o = this.options,
				ul = this.element,
				suggestion = $('input.suggestionText', $(ul).parent()),
				idea = suggestion.val(),
				curses = /(bitch|\bass\b|asshole|crap|cunt|\bcock\b|\bdick\b|fuck|fucking|fucker|motherfucker|nigger|penis|pussy|queer|shit|slut|twat|whore)/gi;
			if (!curses.test(idea)) {
				this._ajax({ action: 'submit', content: idea, market: o.market, concept: o.concept, culture: o.culture }, function (data) {
					suggestion.val('');
					if ( data.html ) {
						$.publish('/suggests/newIdea', [ idea ]);
						$(data.html).appendTo(ul).find('span.up').trigger('click').end().slideDown();
						$(ul).next().html(o.translations[0]); // o.translations[0] = 'Thank you for submitting an idea.'
					}
				});
			}
		},
		_vote: function (source) {
			var elem		= $(source),
				parent		= elem.parent(),
				li			= elem.closest('li'),
				ratingDiv	= li.find('.rating'),
				id			= li.attr('id').replace('s',''),
				v			= 1,
				o			= this.options;

			// If the user's already voted
			if(parent.hasClass('inactive')){
				return false;
			}

			parent.removeClass('active').addClass('inactive');

			if(elem.hasClass('down')){
				v = -1;
			}

			// Sending an AJAX request
			this._ajax({ action:'vote', vote:v, 'id':id, market: o.market, concept: o.concept, user: o.user });
			return true;
		}
	});
/*
	translations[0] = Thank you for submitting an idea.
	translations[1] = Click on the box in the background to switch between the concept and the suggestions
	translations[2] = Add your own idea by typing it in the box below and clicking "Add Idea"
	translations[3] = Add Idea
	translations[4] = Now we would like to ask you how you would improve these products. In addition to submitting your own ideas, you may also vote on the ideas of others.
	translations[5] = Click next to begin.
*/

	ICE = window.ICE || {};
	ICE.suggestion = {
		o: {},
		market: 0,
		user: 'debug',
		visibles: [],
		getConcepts: function () {
			var data = 'action=getConcepts&u='+ICE.suggestion.o.user+'&m='+ICE.suggestion.o.market;
			if (ICE.suggestion.o.culture != null) {
				data += '&c='+ICE.suggestion.o.culture;
			}
			$.ajax({
				url: '//presentation.infosurv.com/ice/rd3/actions.php',
				data: data,
				dataType: 'jsonp',
				success: function(data) {
					$.publish('/suggests/concepts', [ data, ICE.suggestion.o ]);
				}
			});
		},
		init: function (market, user, visibles, question, translations, culture) {
			var i = ICE.suggestion;
			i.o.market = market;
			i.o.user = user;
			i.o.visibles = visibles;
			i.o.question = question;
			i.o.culture = culture;
			i.o.translations = translations || ['Thank you for submitting an idea.', 'Click on the box in the background to switch between the concept and the suggestions', 'Add your own idea by typing it in the box below and clicking "Add Idea"', 'Add Idea', 'Now we would like to ask you how you would improve your favorite concept(s). In addition to submitting your own ideas, you may also vote on the ideas of others.', 'Click next to begin.'];
			i.getConcepts();
		}
	};
	
	var height = function (concept) {
		var conceptBox = $('div.concept').eq(concept),
			hsb = conceptBox.find('div.suggest-box').outerHeight() + 50,
			hci = conceptBox.find('div.conceptImage').outerHeight() + 100;
		if (!concept) {
			return conceptBox.outerHeight() + 100;
		}
		return (hsb >= hci ? hsb : hci);
	};
	
	$.subscribe('/suggests/grabbed', function (concept) {
		var holder = $('div.conceptholder'),
			h = height(0)-70;
		holder.css('height', h);
		$.publish('/suggests/activateConcept', [ "activate", 1 ]);
	});
	
	$.subscribe('/suggests/concepts', function (data, options) {
		var results = "",
			count = 1,
			tmpl = '<div class="concept" id="concept{{id}}"><p style="color:gray;font-size:.7em;text-align:center;margin-top:-25px;">' + options.translations[1] + '</p><div class="suggest-box"><div class="heading"><h1>' + options.question + '<em>{{name}}</em></h1></div><ul class="suggestions"></ul><div class="suggest"><label for="ST{{id}}">' + options.translations[2] + '</label><div><input type="text" id="ST{{id}}" class="suggestionText" maxlength="255" /><input type="submit" value="' + options.translations[3] + '" class="submitSuggestion" /></div></div></div><div class="conceptImage">{{concept}}</div></div>',
			html = '',
			intro = '<div class="concept" id="intro"><p>' + options.translations[4] + '</p><h2>' + options.translations[5] + '</h2></div>';
			
		if (!data.length) { return; }
		
		var current, i, ind, max, _ref;
		max = 0;
		ind = [];
		for (i = _ref = options.visibles.length - 1; _ref <= 0 ? i <= 0 : i >= 0; _ref <= 0 ? i++ : i--) {
		  current = Number(options.visibles[i]);
		  if (current > max) {
		    ind = [];
		    ind.push(i);
		    max = current;
		  } else if (current === max) {
		    ind.push(i);
		  }
		}
		
		html = $.map(data, function (concept, index) {
			// if the item isnt meant to be visible, skip it
			var __indexOf = Array.prototype.indexOf || function(item) {
			  for (var i = 0, l = this.length; i < l; i++) {
			    if (this[i] === item) return i;
			  }
			  return -1;
			};
			if (__indexOf.call(ind, index) < 0) {
			  return null;
			}
			count = count + 1;
			return tmpl
				.replace(/\{\{id\}\}/g, concept.cid)
				.replace(/\{\{name\}\}/g, concept.name)
				.replace(/\{\{concept\}\}/g, concept.content);
		}).join('');
		
		$('div.conceptholder')
			.append(intro)
			.append(html)
			.css({ width: (count * 960) + 'px' });
		$('ul.suggestions').each(function () {
			var cid = $(this).closest('div.concept').attr('id').replace('concept','');
			$(this).suggestions({ market: options.market, concept: cid, user: options.user, translations: options.translations, culture: options.culture });
		});
		$('div.concept').each(function (index) {
			var	concept = $(this),
				image = concept.find('div.conceptImage'),
				suggest = concept.find('div.suggest-box'),
				background = concept.find('div.background');
			
			image.bind('click', function () {
				$.publish('/suggests/switchActive', [ index ]);
			});
			background.live('click', function () {
				$.publish('/suggests/switchActive', [ index ]);
			});
			
		});
		$.publish('/suggests/buttons', [ count ]);
	});
	
	$.subscribe('/suggests/buttons', function (count) {
		// As the buttons appear below this code, we have to delay the changing of them until document load
		$(function () {
			var counter = 1,
				holder = $('div.conceptholder');
			
			$('a.next').unbind().bind('click',function(){
				if(counter<count){
					counter = counter +1;
					$.publish('/suggests/activateConcept', [ "activate", counter ]);
					// grab the height of the next object
					var h = height(counter-1);
					holder.animate({
						marginLeft: '-=' + 960 + 'px',
						height: h + 'px'
					}, 500, function () {
						$.publish('/suggests/activateConcept', [ "deactivate", counter-1 ]);
					});
				} else {
					$('input.submit-button[name=next]').trigger('click');
				}
				return false;
			});

			$('a.back').unbind().bind('click',function(){
				if(counter>1){
					counter = counter - 1;
					$.publish('/suggests/activateConcept', [ "activate",counter ]);
					// grab the height of the previous object
					var h = height(counter-1);
					holder.animate({
						marginLeft: '+=' + 960 + 'px',
						height: h + 'px'
					}, 500, function () {
						$.publish('/suggests/activateConcept', [ "deactivate", counter+1 ]);
					});
				} else {
					$('input.submit-button[name=back]').trigger('click');
				}
				return false;
			});
		});
	});
	
	$.subscribe('/suggests/activateConcept', function (action,index1) {
		var index0 = index1 - 1,
			concept = $('div.concept').eq(index0),
			image = concept.find('div.conceptImage'),
			suggest = concept.find('div.suggest-box');
		
		if (action === "activate") {
			image.addClass('background');
			suggest.addClass('foreground');
		} else if (action === "deactivate") {
			image.removeClass('foreground background');
			suggest.removeClass('foreground background');
		}
	});
	
	$.subscribe('/suggests/switchActive', function (index) {
		var	concept = $('div.concept').eq(index),
			image = concept.find('div.conceptImage'),
			suggest = concept.find('div.suggest-box');
		
		image.toggleClass('foreground background');
		suggest.toggleClass('foreground background');
	});
	
	$.subscribe('/suggests/newIdea', function (idea) {
		var holder = $('div.conceptholder'),
			holderHeight = holder.height(),
			addition = Math.ceil(idea.length / 50) * 49;
		
		holder.css('height', (holderHeight + addition) + 'px');
	});
	
}(jQuery));