(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
var Core = require('./core');

var classes  = require('./core/-classes');
var touches  = require('./core/-touches');
var changed  = require('./core/-changed');
// var scale   = require('./core/-scale');
var keys     = require('./core/-keys');
var defaults = require('./-defaults');

'use strict';

/**
 * Update [old] object with [newer] values
 * @return {obj}
 */
function deepMerge(target, src) {
  var array = Array.isArray(src);
  var dst = array && [] || {};

  if (array) {
    target = target || [];
    dst = dst.concat(target);
    src.forEach(function(e, i) {
      if (typeof dst[i] === 'undefined') {
        dst[i] = e;
      } else if (typeof e === 'object') {
        dst[i] = deepMerge(target[i], e);
      } else {
        if (target.indexOf(e) === -1) {
          dst.push(e);
        }
      }
    });
  } else {
    if (target && typeof target === 'object') {
      Object.keys(target).forEach(function (key) {
        dst[key] = target[key];
      })
    }
    Object.keys(src).forEach(function (key) {
      if (typeof src[key] !== 'object' || !src[key]) {
        dst[key] = src[key];
      }
      else {
        if (!target[key]) {
          dst[key] = src[key];
        } else {
          dst[key] = deepMerge(target[key], src[key]);
        }
      }
    });
  }

  return dst;
};

/**
 * Bee3D Slider
 * @param {HTMLElement}    slider parent
 * @param {Object}         options (optional)
 */
function Bee3D( parent, options ) {
  this.options = deepMerge(defaults, options);

  this.init(parent);
}

Bee3D.prototype = {
  init: function(parent) {
    var opts = this.options,
      slides = parent.querySelectorAll( opts.selector );

    // create slider instance
    this.el = Core( slides );
    this.el.parent = parent;

    // init plugins
    this.plugins();

    // activate the first slide
    this.el.slide( this.options.focus );
    // assign effect to slider--parent
    classie.add(this.el.parent, 'bee3D--effect__'+this.options.effect);

    // initialize events
    this.events();
    this.slideEvents();

    // onInit callback
    this.options.onInit();

    // let ourselves know we've started
    return this.el.initialized = true;
  },

  plugins: function(){
    var self = this,
      opts = self.options,
      funcs = [
        classes(opts),
        changed(opts.onChange)
      ];

    // if turned on...
    if (opts.listeners.keys) funcs.push( keys() );
    if (opts.listeners.touches) funcs.push( touches() );

    // init plugin funcs
    (funcs || []).forEach(function(plugin){
      plugin(self.el);
    });
  },

  events: function(){
    var opts = this.options;

    if (opts.sync.enabled) this.sync();
    if (opts.ajax.enabled) this.ajax();
    if (opts.loop.enabled) this.loop();
    if (opts.autoplay.enabled) this.autoplay();
    if (opts.navigation.enabled) this.navigation();
    if (opts.listeners.scroll) this.mouseScroll();
    if (opts.listeners.drag) this.mouseDrag();
  },

  slideEvents: function(slides){
    var opts = this.options,
      slides = slides || this.el.slides;

    if (opts.shadows.enabled) this.shadows(slides);
    if (opts.parallax.enabled) this.parallax(slides);
    if (opts.listeners.clicks) this.clickInactives(slides);
  },

  sync: require('./features/sync'),
  ajax: require('./features/ajax'),
  loop: require('./features/loop'),
  shadows: require('./features/shadows'),
  autoplay: require('./features/autoplay'),
  navigation: require('./features/navigation'),
  parallax: require('./features/parallax'),

  clickInactives: require('./features/clickInactives'),
  mouseScroll: require('./features/mouseScroll'),
  mouseDrag: require('./features/mouseDrag'),

  destroy: require('./core/-destroy'),

  // --- helpers
  listenToHover: require('./features/autoplay/-listenToHover'),

};

// expose Bee3D globally
window.Bee3D = Bee3D;

},{"./-defaults":2,"./core":8,"./core/-changed":3,"./core/-classes":4,"./core/-destroy":5,"./core/-keys":6,"./core/-touches":7,"./features/ajax":10,"./features/autoplay":12,"./features/autoplay/-listenToHover":11,"./features/clickInactives":13,"./features/loop":14,"./features/mouseDrag":15,"./features/mouseScroll":16,"./features/navigation":17,"./features/parallax":18,"./features/shadows":19,"./features/sync":20}],2:[function(require,module,exports){
module.exports = {
  // main wrapper, watches for gestures
  wrapper: document.body,
  // slides' selector class
  selector: '.bee3D--slide',
  // scroll class to apply: coverflow|classic|cube|carousel|concave|wave|arc|spiral-left|spiral-right
  effect: 'coverflow',
  // slide to focus on init
  focus: 0,
  listeners: {
    keys: false, // keyboard keys
    touches: false, // touch/swipe (mobile)
    clicks: false, // click non-active slides to show
    scroll: false, // mouse scroll wheel
    drag: false, // mouse drag/swipe
  },
  navigation: {
    enabled: false,
    next: '.bee3D--nav__next',
    prev: '.bee3D--nav__prev'
  },
  ajax: {
    // use ajax calls?
    enabled: false,
    // API url to query
    path: null,
    // when to make the request? eg: 2nd to last slide
    when: 2,
    // max # of times to query this path/URL
    // null == infinite fetches
    maxFetches: null, 
    // use data attributes to build new slides
    constructor: function(data){
      return '<p>'+data.content+'</p>';
    }
  },
  autoplay: {
    // use autoplay ?
    enabled: false,
    // autoplay time (ms) per slide
    speed: 5000,
    // pause autoplay on hover ?
    pauseHover: false,
  },
  loop: {
    // loop to start ?
    enabled: false,
    // continuous loop effect ?
    continuous: false,
    // # of slides to offset to make loop seem infinite
    // different for each effect
    offset: 2
  },
  sync: {
    enabled: false,
    targets: []
  },
  parallax: {
    // use parallax effect ?
    enabled: false,
    // class name to add
    className: 'bee3D--parallax',
    // data-depth
    friction: 0.7,
    // parallax library settings
    settings: {
      relativeInput: true,
      clipRelativeInput: true,
      calibrateX: true,
      calibrateY: true,
      scalarX: 4.0,
      scalarY: 5.0,
      frictionX: 0.1,
      frictionY: 0.1
    }
  },
  shadows: {
    // cast shadows under slides?
    enabled: false,
    // HTML to add
    template: [
      '<div class="bee3D--shadow-wrapper">',
        '<div class="bee3D--shadow">',
          '<span></span>',
        '</div>',
      '</div>'
    ].join('')
  },
  // callbacks
  onInit: function() {},
  onChange: function() {},
  onDestroy: function() {}
};

},{}],3:[function(require,module,exports){
module.exports = function(cb){
  return function(slider){
    slider.on('activate', function(e) {
      if (slider.initialized) return cb(e);
    });
  };
};

},{}],4:[function(require,module,exports){
module.exports = function(opts) {
  var baseClass = 'bee3D--';
  var isLoop = opts.loop.continuous;
  var loopOffset = opts.loop.offset;

  return function(slider) {
    var length = slider.slides.length;

    var addClass = function(el, cls) {
        classie.add(el, baseClass + cls);
      },

      removeClass = function(el, cls) {
        el.className = el.className.replace(new RegExp(baseClass + cls +'(\\s|$)', 'g'), ' ').trim();
      },

      deactivate = function(el, index) {
        var active = slider.slide(),
          offset = index - active,
          offsetClass = offset > 0 ? 'after' : 'before';

        if (isLoop) {
          var boundary = length-loopOffset-1;

          if (offset >= boundary) {
            offsetClass = 'before';
            offset = length-offset;
          }

          if (offset <= -boundary) {
            offsetClass = 'after';
            offset = length+offset;
          }
        }

        ['before(-\\d+)?', 'after(-\\d+)?', 'slide__active', 'slide__inactive'].map(removeClass.bind(null, el));

        if (index !== active) {
          ['slide__inactive', offsetClass, offsetClass + '-' + Math.abs(offset)].map(addClass.bind(null, el));
        }
      };

    // add parent class if not already there
    addClass(slider.parent, 'parent');

    // add bee3D--slide class to all slides ONLY if options.selector was changed
    var isDefault = opts.slideSelector === '.bee3D--slide';
    if (!isDefault) slider.slides.map(function(el) { addClass(el, 'slide') });

    slider.on('activate', function(e) {
      slider.slides.map(deactivate);
      addClass(e.slide, 'slide__active');
      removeClass(e.slide, 'slide__inactive');
    });
  };
};

},{}],5:[function(require,module,exports){
module.exports = function(){
  // var elems = this.el.slides.concat(this.el.parent);
  var parent = this.el.parent;
  var slides = this.el.slides;
  var regex = new RegExp('bee3D-(.*)', 'g');

  parent.className = parent.className.replace(regex, '');

  // remove all Bee3D classes
  var isBare = this.options.selector === '.bee3D--slide';
  for (var i = 0; i < slides.length; i++) {
    slides[i].className = isBare ? 'bee3D--slide' : slides[i].className.replace(regex, '')
  }

  this.el.fire('destroy');

  // onDestroy callback
  this.options.onDestroy();
};

},{}],6:[function(require,module,exports){
module.exports = function(options) {
  return function(slider) {
    var isHorizontal = options !== 'vertical';

    var handler = function(e){
      if (e.which == 34 || // PAGE DOWN
        e.which == 32 || // SPACE
        (isHorizontal && e.which == 39) || // RIGHT
        (!isHorizontal && e.which == 40) // DOWN
      ) { slider.next(); }

      if (e.which == 33 || // PAGE UP
        (isHorizontal && e.which == 37) || // LEFT
        (!isHorizontal && e.which == 38) // UP
      ) { slider.prev(); }
    }

    document.addEventListener('keydown', handler);
    slider.on('destroy', function(){
      console.log( 'keys: destroy' );
      document.removeEventListener('keydown', handler);
    })
  };
};

},{}],7:[function(require,module,exports){
module.exports = function(options) {
  return function(slider) {
    var axis = options == 'vertical' ? 'Y' : 'X',
      startPosition,
      delta;

    var handleStart = function(e) {
      if (e.touches.length == 1) {
        startPosition = e.touches[0]['page' + axis];
        delta = 0;
      }
    },
    handleMove = function(e) {
      if (e.touches.length == 1) {
        e.preventDefault();
        delta = e.touches[0]['page' + axis] - startPosition;
      }
    },
    handleEnd = function() {
      if (Math.abs(delta) > 50) {
        slider[delta > 0 ? 'prev' : 'next']();
      }
    };

    slider.parent.addEventListener('touchstart', handleStart);
    slider.parent.addEventListener('touchmove', handleMove);
    slider.parent.addEventListener('touchend', handleEnd);

    slider.on('destroy', function(){
      console.log( 'touches: destroy' );
      slider.parent.removeEventListener('touchstart', handleStart);
      slider.parent.removeEventListener('touchmove', handleMove);
      slider.parent.removeEventListener('touchend', handleEnd);
    })
  };
};

},{}],8:[function(require,module,exports){
module.exports = function( slides ) {
  var slides = [].slice.call( slides ),
    activeSlide = slides[-1],
    listeners = {},

    getActiveIndex = function(){
      return slides.indexOf(activeSlide);
    },

    touch = function(customData){
      return fire('activate', createEventData(activeSlide, customData));
    },

    activate = function(index, customData) {
      if (!slides[index]) return;

      fire('deactivate', createEventData(activeSlide, customData));
      activeSlide = slides[index];

      touch(customData);
    },

    slide = function(index, customData) {
      var activeIndex = getActiveIndex();

      // if already on this slideIndex, do nothing
      if (activeIndex==index) return;

      if (arguments.length) {
        fire('slide', createEventData(slides[index], customData)) && activate(index, customData);
      } else {
        return activeIndex;
      }
    },

    step = function(offset, customData) {
      var slideIndex = slides.indexOf(activeSlide) + offset;

      fire(offset > 0 ? 'next' : 'prev', createEventData(activeSlide, customData)) && activate(slideIndex, customData);
    },

    on = function(eventName, callback) {
      (listeners[eventName] || (listeners[eventName] = [])).push(callback);

      return function() {
        listeners[eventName] = listeners[eventName].filter(function(listener) {
          return listener !== callback;
        });
      };
    },

    fire = function(eventName, eventData) {
      return (listeners[eventName] || [])
        .reduce(function(notCancelled, callback) {
          return notCancelled && callback(eventData) !== false;
        }, true);
    },

    createEventData = function(el, eventData) {
      eventData = eventData || {};
      eventData.index = slides.indexOf(el);
      eventData.slide = el;
      return eventData;
    };

  // return the slider object
  return {
    on: on,
    fire: fire,
    touch: touch,
    slide: slide,
    next: step.bind(null, 1),
    prev: step.bind(null, -1),
    slides: slides
  };
};

},{}],9:[function(require,module,exports){
(function (global){
// https://github.com/yanatan16/nanoajax/blob/master/index.js
exports.init = function (params, callback) {
  if (typeof params == 'string') params = {url: params}
  var headers = params.headers || {},
    body = params.body,
    method = params.method || (body ? 'POST' : 'GET'),
    withCredentials = params.withCredentials || false

  var req = getRequest()

  req.onreadystatechange = function () {
    if (req.readyState == 4) callback(req.status, req.responseText, req)
  }

  if (body) {
    setDefault(headers, 'X-Requested-With', 'XMLHttpRequest')
    setDefault(headers, 'Content-Type', 'application/x-www-form-urlencoded')
  }

  req.open(method, params.url, true)

  // has no effect in IE
  // has no effect for same-origin requests
  // has no effect in CORS if user has disabled 3rd party cookies
  req.withCredentials = withCredentials

  for (var field in headers) req.setRequestHeader(field, headers[field])

  req.send(body)
}

function getRequest() {
  if (global.XMLHttpRequest)
    return new global.XMLHttpRequest;
  else
    try { return new global.ActiveXObject("MSXML2.XMLHTTP.3.0"); } catch(e) {}
  throw new Error('no xmlhttp request able to be created')
}

function setDefault(obj, key, value) {
  obj[key] = obj[key] || value
}

}).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
},{}],10:[function(require,module,exports){
var AJAX = require('./-lib');

module.exports = function(){
  var self = this,
    opts = self.options.ajax,
    buffer = opts.when, // when 2nd to last slide from end
    path = opts.path, // URL to query
    remaining = opts.maxFetches, // cache # of times to query Path
    constructor = opts.constructor;

  var slides = self.el.slides,
    length = slides.length,
    slideClass = self.options.selector.substring(1), // bee3D selector, without the '.'

    handleActivate = function(event){
      if (length-buffer == event.index) {
        if (opts.maxFetches) {
          if (remaining>0) return fetchNew();
        } else {
          return fetchNew();
        }
      }
    },

    addItem = function(item){
      self.el.parent.appendChild( item );
      self.el.slides.push( item );
    },

    constructItems = function(data){
      var added = [];

      for (var i = 0; i < data.length; i++) {
        var item = document.createElement('section');
          item.className = slideClass;
          item.innerHTML = '<div class="bee3D--inner">' + constructor( data[i] ) + '</div>';

        // add item to new array of items
        added.push(item);

        // append new item to parent
        addItem(item);
      }

      // now update our cached length var
      length = self.el.slides.length;

      // attach events to new slides
      self.slideEvents( added );

      // decrease number of 'remaining' we have left
      if (opts.maxFetches) remaining--;

      // fire a fake 'activate' event, to trigger classes() plugin
      return self.el.touch();
    },

    fetchNew = function(){
      AJAX.init(path, function(statusCode, response) {
        return (statusCode==200) ? constructItems( JSON.parse(response).data ) : console.error( 'Error fetching new items!' );
      });
    };

  // listen to slide changes...
  this.el.on('activate', handleActivate);
};

},{"./-lib":9}],11:[function(require,module,exports){
module.exports = function(elem){
  var self = this;

  var handleOver = function(){
    self.el.fire('pauseAutoplay'); // pause timer
  },
  handleOut = function(){
    self.el.fire('resetAutoplay'); // reset timer
  };

  elem.addEventListener('mouseover', handleOver);
  elem.addEventListener('mouseout', handleOut);

  self.el.on('destroy', function(){
    console.log( 'listenToHover: destroy' );
    elem.removeEventListener('mouseover', handleOut);
    elem.removeEventListener('mouseout', handleOver);    
  })
};

},{}],12:[function(require,module,exports){
module.exports = function(){
  var self = this,

    start = function() {
      self.timer = setTimeout(function(){
        self.el.next();
      }, self.options.autoplay.speed);
    },

    stop = function(){
      clearTimeout(self.timer);
    },

    reset = function(){
      stop(); start();
    };

  // begin autoplay immediately
  start();

  // start on 'resumeAutoplay' event
  self.el.on('resumeAutoplay', start);

  // pause on 'pauseAutoplay' event
  self.el.on('pauseAutoplay', stop);

  // reset on 'resetAutoplay' event
  self.el.on('resetAutoplay', reset);

  // on each 'activate' event, reset timer!
  // -- aka, reset timer on ANY slide change
  self.el.on('activate', reset);

  // pause if hovering the active slide 
  if (self.options.autoplay.pauseHover) {
    self.el.on('activate', function(event){
      self.listenToHover( event.slide ); // newly-activated slide
    });
  }

  self.el.on('destroy', function(){
    console.log( 'autoplay: destroy' );
    stop()
  })

};

},{}],13:[function(require,module,exports){
module.exports = function(slides){
  var self = this,
    handleClick = function(e){

      // get this index
      var index = slides.indexOf(this);

      // set slider to this index
      return self.el.slide(index);
    };

  for (var i = 0; i < slides.length; i++) {
    // allow clicks on inactive slides
    slides[i].style.pointerEvents = 'auto';
    slides[i].style.cursor = 'pointer';

    // assign click listener to all, initially
    slides[i].addEventListener('click', handleClick);
  }

  // remove click listener on active slide
  this.el.on('activate', function(event){
    event.slide.removeEventListener('click', handleClick);
  });

  // reassign click listener when active-slide deactivates
  this.el.on('deactivate', function(event){
    event.slide.addEventListener('click', handleClick);
  });

  this.el.on('destroy', function(){
    console.log( 'clickInactives: destroy' );
    slides.map(function(slide){
      slide.removeAttribute('style');
      slide.removeEventListener('click', handleClick);
    })
  })
};

},{}],14:[function(require,module,exports){
module.exports = function(){
  var slider = this.el;
  
  slider.on('prev', function(e) {
    if (e.index == 0) slider.slide(slider.slides.length - 1);
  });

  slider.on('next', function(e) {
    if (e.index == slider.slides.length - 1) slider.slide(0);
  });
};

},{}],15:[function(require,module,exports){
module.exports = function(){
  var self = this,
    parent = this.el.parent,
    start, delta,

    handleDown = function(e) {
      start = e.x;
      delta = 0;
    },

    handleMove = function(e) {
      e.preventDefault();
      delta = e.x - start;
    },

    handleUp = function() {
      if (Math.abs(delta) > 50) {
        self.el[delta > 0 ? 'prev' : 'next']();
      }
    };


  // mouse cursor
  classie.add(parent, 'draggable');
  parent.addEventListener('mousedown', handleDown);
  parent.addEventListener('mousemove', handleMove);
  parent.addEventListener('mouseup', handleUp);

  this.el.on('destroy', function(){
    console.log( 'mousedrag: destroy' );
    classie.remove(parent, 'draggable')
    parent.removeEventListener('mousedown', handleDown);
    parent.removeEventListener('mousemove', handleMove);
    parent.removeEventListener('mouseup', handleUp);    
  })
};

},{}],16:[function(require,module,exports){
module.exports = function(){
  var self = this,
    parent = this.el.parent,
    handleScroll = function(event){
      var delta = event.wheelDelta || -event.detail;
      return (delta < 0) ? self.el.next() : self.el.prev();
    };

  parent.addEventListener('mousewheel', handleScroll);
  parent.addEventListener('DOMMouseScroll', handleScroll);

  this.el.on('destroy', function(){
    console.log( 'mousescroll: destroy' );
    parent.removeEventListener('mousewheel', handleScroll);
    parent.removeEventListener('DOMMouseScroll', handleScroll);
  })
};

},{}],17:[function(require,module,exports){
module.exports = function(){
  var self = this,
    opts = self.options,
    parent = self.el.parent;

  var listenTo = function(elem, goForward){
    // click to navigate
    elem.addEventListener('click', function(event){
      event.preventDefault();
      goForward ? self.el.next() : self.el.prev();
    });

    // if autoplay && pausehover, pause on hovering the arrow
    if (opts.autoplay.enabled && opts.autoplay.pauseHover) {
      self.listenToHover( elem );
    }
  };

  var prev = parent.querySelector( opts.navigation.prev ),
    next = parent.querySelector( opts.navigation.next );

  if (next) listenTo(next, true);
  if (prev) listenTo(prev, false);
};

},{}],18:[function(require,module,exports){
module.exports = function(slides){
  if (typeof Parallax == 'undefined'){
    return console.error( 'You must have the Parallax library loaded in order to use parallax effects!' );
  }

  var self = this,
    opts = self.options,
    useShadow = opts.shadows.enabled;

  var cls = opts.parallax.className,
    fric = opts.parallax.friction,
    settings = opts.parallax.settings,
    // add our parallax classname & friction setting to slide
    applyParallaxTo = function(item){
      classie.add(item, cls);
      item.setAttribute('data-depth', fric);
    };

  // Loop thru our slides, setup parallax-ing
  for (var i = 0; i < slides.length; i++) {
    var slide = slides[i];
    applyParallaxTo( slide.firstElementChild ); // bee3D--inner
    if (useShadow) applyParallaxTo( slide.lastChild );
  }

  // quickly modify settings object
  settings.className = cls;
  
  // now initialize parallax!
  self._parallax = new Parallax(self.el.parent, opts.parallax.settings);
  self.el.parent.style.transformStyle = 'initial'; // reset 'preserve-3D'

  self.el.on('destroy', function(){
    console.log( 'parallax: destroy' );
    // clean our parent
    self.el.parent.removeAttribute('style');
    // disable it
    self._parallax.disable();
    // save layers
    var layers = self._parallax.layers;
    // dump everything
    self._parallax = self._parallax.layers = self._parallax.element = undefined;
    // loop thru layers & reset attributes & styles
    // go from the back, because Parallax ignores 'shadow' layer once 'slide' has been cleansed
    for (var i = layers.length - 1; i >= 0; i--) {
      var item = layers[i];
      classie.remove(item, cls);
      item.removeAttribute('data-depth');
      item.removeAttribute('style');
    }
  })
};

},{}],19:[function(require,module,exports){
module.exports = function(slides){
  var template = this.options.shadows.template;

  slides.forEach(function(slide){
    // var exists = false;
    // if (slide.querySelector(cls)) exists = true;
    // if (!exists) 
    slide.innerHTML += template;
  });

  this.el.on('destroy', function(){
    console.log( 'shadows: destroy' );
    slides.forEach(function(slide){
      slide.removeChild(slide.lastChild) // assuming is shadow-layer always
    })
  })
};

},{}],20:[function(require,module,exports){
module.exports = function(){
  var targets = this.options.sync.targets,

    syncSliders = function(event){
      var index = event.index;

      // loop thru all partner sliders & force-sync their active slide index
      for (var i = 0; i < targets.length; i++) {
        window[ targets[i] ].el.slide( index );
      }
    };

  // when this slider activates a slide, sync others
  this.el.on('activate', syncSliders);
};

},{}]},{},[1]);
