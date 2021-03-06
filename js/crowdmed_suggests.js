// Generated by CoffeeScript 1.3.3

/*
 Simple Pub/Sub Implementation for jQuery

 Inspired by work from Peter Higgins (https://github.com/phiggins42/bloody-jquery-plugins/blob/master/pubsub.js)

 This is about the simplest way to write a pubsub JavaScript implementation for use with jQuery.
*/


(function() {

  (function($) {
    var topics;
    topics = {};
    $.publish = function(topic, args) {
      var i, thisArgs, thisTopic, _i, _len;
      if (topics[topic]) {
        thisTopic = topics[topic];
        thisArgs = args || [];
        for (_i = 0, _len = thisTopic.length; _i < _len; _i++) {
          i = thisTopic[_i];
          i.apply($, thisArgs);
        }
      }
    };
    $.subscribe = function(topic, callback) {
      if (!topics[topic]) {
        topics[topic] = [];
      }
      topics[topic].push(callback);
      return {
        topic: topic,
        callback: callback
      };
    };
    $.unsubscribe = function(handle) {
      var index, thisTopic, topic, _i, _len;
      topic = handle.topic;
      if (topics[topic]) {
        thisTopic = topics[topic];
        for (index = _i = 0, _len = thisTopic.length; _i < _len; index = ++_i) {
          topic = thisTopic[index];
          if (topic === handle.callback) {
            thisTopic.splice(index, 1);
          }
        }
      }
    };
  })(jQuery);

  /*
   CrowdMed - Suggestion Box
   idea taken from http://tutorialzine.com/2010/08/ajax-suggest-vote-jquery-php-mysql/ and heavily modified
  */


  (function($, exports) {
    var CrowdMed, ajax_src;
    ajax_src = '//www2.infosurv.com/CrowdMed/ajax.php';
    $.widget("CrowdMed.suggestions", {
      options: {
        pid: 1,
        user: 'debug',
        source: ajax_src,
        type: 'jsonp',
        translations: ['Thank you for submitting an idea.'],
        culture: 'en-US'
      },
      _create: function() {
        var elem, o,
          _this = this;
        o = this.options;
        elem = $(this.element);
        this._getData();
        $('input.submitSuggestion', elem.parent()).bind('click', function(event) {
          event.preventDefault();
          return _this._addSuggestion();
        });
        $('div.vote span', elem).live('click', function(event) {
          return _this._vote(event.target);
        });
      },
      destroy: function() {
        $.Widget.prototype.destroy.apply(this, arguments);
      },
      _ajax: function(data, successfn) {
        var o;
        o = this.options;
        $.ajax({
          url: o.source,
          dataType: o.type,
          data: data,
          success: successfn
        });
      },
      _getData: function() {
        var elem, o;
        elem = this.element;
        o = this.options;
        this._ajax({
          action: 'grab',
          user: o.user,
          pid: o.pid,
          culture: o.culture
        }, function(data) {
          elem.append(data);
          return $.publish('/suggests/resize');
        });
      },
      _addSuggestion: function() {
        var curses, idea, o, suggestion, ul;
        o = this.options;
        ul = this.element;
        suggestion = $('input.suggestionText', $(ul).parent());
        idea = suggestion.val();
        curses = /(bitch|\bass\b|asshole|crap|cunt|cock|dick|fuck|fucking|fucker|motherfucker|nigger|penis|pussy|queer|shit|slut|twat|whore)/gi;
        if (!curses.test(idea)) {
          this._ajax({
            action: 'submit',
            content: idea,
            pid: o.pid,
            culture: o.culture
          }, function(data) {
            suggestion.val('');
            if (data.html) {
              $.publish('/suggests/newIdea', [idea]);
              $(data.html).appendTo(ul).find('span.up').trigger('click').end().slideDown();
              return $(ul).next().html(o.translations[0]);
            }
          });
        }
      },
      _vote: function(source) {
        var elem, id, li, o, parent, ratingDiv, v;
        elem = $(source);
        parent = elem.parent();
        li = elem.closest('li');
        ratingDiv = li.find('.rating');
        id = li.attr('id').replace('s', '');
        v = 1;
        o = this.options;
        if (parent.hasClass('inactive')) {
          return false;
        }
        parent.removeClass('active').addClass('inactive');
        if (elem.hasClass('down')) {
          v = -1;
        }
        this._ajax({
          action: 'vote',
          vote: v,
          'id': id,
          pid: o.pid,
          user: o.user
        });
        return true;
      }
    });
    CrowdMed = exports.CrowdMed || {};
    CrowdMed.suggestion = (function() {
      var addEventHandlers, getSurveyId, o, user;
      o = {};
      user = 'debug';
      getSurveyId = function() {
        var project_name;
        project_name = $('#PdcSurveyName').val();
        if (project_name != null) {
          return project_name.split('/')[3];
        } else {
          return '111111';
        }
      };
      addEventHandlers = function() {
        var background, concept, image, suggest;
        concept = $('div.concept');
        image = concept.find('div.conceptImage');
        suggest = concept.find('div.suggest-box');
        background = concept.find('div.background');
        image.addClass('background');
        suggest.addClass('foreground');
        image.bind('click', function() {
          return $.publish('/suggests/switchActive');
        });
        return background.live('click', function() {
          return $.publish('/suggests/switchActive');
        });
      };
      return function(user, translations, culture) {
        o.user = user;
        o.pid = getSurveyId();
        $('ul.suggestions').suggestions(o);
        return addEventHandlers();
      };
    })();
    exports.CrowdMed = CrowdMed;
    $.subscribe('/suggests/switchActive', function() {
      var concept, image, suggest;
      concept = $('div.concept');
      image = concept.find('div.conceptImage');
      suggest = concept.find('div.suggest-box');
      image.toggleClass('foreground background');
      suggest.toggleClass('foreground background');
    });
    $.subscribe('/suggests/newIdea', function(idea) {
      var addition, holder, holderHeight;
      holder = $('div.conceptholder');
      holderHeight = holder.height();
      addition = Math.ceil(idea.length / 50) * 49;
      holder.css('height', "" + (holderHeight + addition) + "px");
    });
    $.subscribe('/suggests/resize', function() {
      var cb, final, sb;
      sb = $('.suggest-box').outerHeight() + 70;
      cb = $('.conceptImage').outerHeight() + 110;
      final = sb >= cb ? sb : cb;
      return $('.conceptholder').css('height', final + 'px');
    });
  })(jQuery, window);

}).call(this);
