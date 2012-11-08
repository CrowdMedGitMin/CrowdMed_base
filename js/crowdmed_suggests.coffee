###
 Simple Pub/Sub Implementation for jQuery

 Inspired by work from Peter Higgins (https://github.com/phiggins42/bloody-jquery-plugins/blob/master/pubsub.js)

 This is about the simplest way to write a pubsub JavaScript implementation for use with jQuery.
###
do ($=jQuery) ->
  # Cache of all topics
  topics = {}

  # Iterates through all subscribers of a topic and invokes their callback,
  # passing optional arguments.
  $.publish = (topic, args) ->
    if topics[topic]
      thisTopic = topics[topic]
      thisArgs = args || []
      
      i.apply($, thisArgs) for i in thisTopic
    return

  # Returns a handle needed for unsubscribing
  $.subscribe = (topic, callback) ->
    topics[topic] = [] unless topics[topic]
    topics[topic].push(callback)

    {
      topic: topic
      callback: callback
    }

  # Removes the subscriber from the particular topic its handle was assigned to
  $.unsubscribe = (handle) ->
    topic = handle.topic

    if topics[topic]
      thisTopic = topics[topic]
      for topic, index in thisTopic
        if topic is handle.callback
          thisTopic.splice(index, 1)
    return
  return

###
 CrowdMed - Suggestion Box
 idea taken from http://tutorialzine.com/2010/08/ajax-suggest-vote-jquery-php-mysql/ and heavily modified
###
do ($ = jQuery, exports = window) ->
  ajax_src = '//www2.infosurv.com/CrowdMed/ajax.php'
  $.widget "CrowdMed.suggestions", 
    options: 
      pid:          1
      user:         'debug'
      source:       ajax_src
      type:         'jsonp'
      translations: ['Thank you for submitting an idea.']
      culture:      'en-US'
    _create: () ->
      o = @options
      elem = $(@element)
      
      @_getData()
      
      $('input.submitSuggestion', elem.parent()).bind 'click', (event) =>
        event.preventDefault()
        @_addSuggestion()
      $('div.vote span', elem).live 'click', (event) => @_vote(event.target)
      return
    destroy: () ->
      $.Widget.prototype.destroy.apply( this, arguments )
      return
    _ajax: (data, successfn) ->
      o = @options
      $.ajax
        url:      o.source
        dataType: o.type
        data:     data
        success:  successfn
      return
    _getData: () ->
      elem = @element
      o    = @options
      
      @_ajax { action: 'grab', pid: o.pid, culture: o.culture }, (data) ->
        elem.append( data )
      return
    _addSuggestion: () ->
      o          = this.options
      ul         = this.element
      suggestion = $('input.suggestionText', $(ul).parent())
      idea       = suggestion.val()
      curses     = /(bitch|\bass\b|asshole|crap|cunt|cock|dick|fuck|fucking|fucker|motherfucker|nigger|penis|pussy|queer|shit|slut|twat|whore)/gi
      if !curses.test(idea)
        this._ajax { action: 'submit', content: idea, market: o.market, concept: o.concept, culture: o.culture }, (data) ->
          suggestion.val('')
          if data.html
            $.publish '/suggests/newIdea', [ idea ]
            $(data.html).appendTo(ul).find('span.up').trigger('click').end().slideDown()
            $(ul).next().html o.translations[0] # o.translations[0] = 'Thank you for submitting an idea.'
      return
    _vote: (source) ->
      elem      = $(source)
      parent    = elem.parent()
      li        = elem.closest('li')
      ratingDiv = li.find('.rating')
      id        = li.attr('id').replace('s','')
      v         = 1
      o         = @options

      # If the user's already voted
      return false if parent.hasClass('inactive')

      parent.removeClass('active').addClass('inactive')

      v = -1 if elem.hasClass('down')

      # Sending an AJAX request
      this._ajax({ action:'vote', vote:v, 'id':id, market: o.market, concept: o.concept, user: o.user })
      return true

  CrowdMed = exports.CrowdMed || {}
  CrowdMed.suggestion = do () ->
    o = {}
    user = 'debug'
    getSurveyId = () ->
      project_name = $('#PdcSurveyName').val()
      if project_name? then pid.val().split('/')[3] else '111111'
    addEventHandlers = () ->
      concept    = $('div.concept')
      image      = concept.find('div.conceptImage')
      suggest    = concept.find('div.suggest-box')
      background = concept.find('div.background')
      
      image.addClass('background')
      suggest.addClass('foreground')

      image.bind 'click', () -> $.publish('/suggests/switchActive', [ index ])
      background.live 'click', () -> $.publish('/suggests/switchActive', [ index ])
    return (user, translations, culture) ->
      o.user         = user
      o.visibles     = visibles
      o.question     = question
      o.culture      = culture
      ###
        translations[0] = Thank you for submitting an idea.
        translations[1] = Click on the box in the background to switch between the concept and the suggestions
        translations[2] = Add your own idea by typing it in the box below and clicking "Add Idea"
        translations[3] = Add Idea
        translations[4] = Now we would like to ask you how you would improve these products. In addition to submitting your own ideas, you may also vote on the ideas of others.
        translations[5] = Click next to begin.
      ###
      o.translations = translations || ['Thank you for submitting an idea.', 'Click on the box in the background to switch between the concept and the suggestions', 'Add your own idea by typing it in the box below and clicking "Add Idea"', 'Add Idea', 'Now we would like to ask you how you would improve your favorite concept(s). In addition to submitting your own ideas, you may also vote on the ideas of others.', 'Click next to begin.']
      o.pid = getSurveyId()
      # Initialize suggestions
      $('ul.suggestions').suggestions(o)

      # add event handlers
      addEventHandlers()
  
# <div class="concept" id="concept">
#   <p style="color:gray;font-size:.7em;text-align:center;margin-top:-25px;">#{options.translations[1]}</p>
#   <div class="suggest-box">
#     <div class="heading">
#       <h1>#{options.question}<em>{{name}}</em></h1>
#     </div>
#     <ul class="suggestions"></ul>
#     <div class="suggest">
#       <label for="ST{{id}}">#{options.translations[2]}</label>
#     <div>
#       <input type="text" id="ST{{id}}" class="suggestionText" maxlength="255" />
#       <input type="submit" value="#{options.translations[3]}" class="submitSuggestion" />
#     </div>
#   </div>
# </div>
# <div class="conceptImage">{{concept}}</div>

  $.subscribe '/suggests/switchActive', (index) ->
    concept = $('div.concept').eq(index)
    image   = concept.find('div.conceptImage')
    suggest = concept.find('div.suggest-box')
    
    image.toggleClass('foreground background')
    suggest.toggleClass('foreground background')
    return
  
  $.subscribe '/suggests/newIdea', (idea) ->
    holder       = $('div.conceptholder')
    holderHeight = holder.height()
    addition     = Math.ceil(idea.length / 50) * 49
    
    holder.css('height', "#{holderHeight + addition}px")
    return
  return
