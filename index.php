<!doctype html>
<html>
<head>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.slim.min.js"></script>
<script>
var Utils = {};
Utils.remove = function(arr, item) {
  if (arr.length) {
    const index = arr.indexOf(item)
    if (index > -1) {
      return arr.splice(index, 1)
    }
  }
}
// By default, Underscore uses ERB-style template delimiters, change the
  // following template settings to use alternative delimiters.
  var _ = _ || {};
  _.templateSettings = {
    evaluate: /<%([\s\S]+?)%>/g,
    interpolate: /{{([\s\S]+?)}}/g,
    escape: /<%-([\s\S]+?)%>/g,
    clickEvent: /@click="([\s\S]+?)"/g,
  };

  // When customizing `templateSettings`, if you don't want to define an
  // interpolation, evaluation or escaping regex, we need one that is
  // guaranteed not to match.
  var noMatch = /(.)^/;

  // Certain characters need to be escaped so that they can be put into a
  // string literal.
  var escapes = {
    "'": "'",
    '\\': '\\',
    '\r': 'r',
    '\n': 'n',
    '\u2028': 'u2028',
    '\u2029': 'u2029'
  };

  var escapeRegExp = /\\|'|\r|\n|\u2028|\u2029/g;

  var escapeChar = function(match) {
    return '\\' + escapes[match];
  };

  // JavaScript micro-templating, similar to John Resig's implementation.
  // Underscore templating handles arbitrary delimiters, preserves whitespace,
  // and correctly escapes quotes within interpolated code.
  // NB: `oldSettings` only exists for backwards compatibility.
  _.template = function(text, settings, oldSettings) {
    if (!settings && oldSettings) settings = oldSettings;
    settings = $.extend({}, settings, _.templateSettings);

    // events
    var events = [];

    // Combine delimiters into one regular expression via alternation.
    var matcher = RegExp([
      (settings.escape || noMatch).source,
      (settings.interpolate || noMatch).source,
      (settings.evaluate || noMatch).source,
      (settings.clickEvent || noMatch).source
    ].join('|') + '|$', 'g');

    // Compile the template source, escaping string literals appropriately.
    var index = 0;
    var source = "__p+='";
    text.replace(matcher, function(match, escape, interpolate, evaluate, clickEvent, offset) {
      source += text.slice(index, offset).replace(escapeRegExp, escapeChar);
      index = offset + match.length;

      if (escape) {
        source += "'+\n((__t=(" + escape + "))==null?'':_.escape(__t))+\n'";
      } else if (interpolate) {
        source += "'+\n((__t=(" + interpolate + "))==null?'':__t)+\n'";
      } else if (evaluate) { 
        source += "';\n" + evaluate + "\n__p+='";
      } else if (clickEvent) { 
        const token = "jd-" + String(Math.random()).substr(7);
        const add = token + " data-event-key=" + token + " data-event onClick=runDomEvent(event,\"" + token + "\")";
        source +=  add;  
        events.push({id: token, event: 'click', func: clickEvent});
        EventManager[token] = {event: 'click', func: clickEvent};
      }

      // Adobe VMs need the match returned to produce the correct offset.
      return match;
    });
    source += "';\n";

    // If a variable is not specified, place data values in local scope.
    if (!settings.variable) source = 'with(obj||{}){\n' + source + '}\n';

    source = "var __t,__p='',__j=Array.prototype.join," +
      "print=function(){__p+=__j.call(arguments,'');};\n" +
      source + 'return __p;\n';
    // console.log(source)
    var render;
    try {
      render = new Function(settings.variable || 'obj', '_', source);
    } catch (e) {
      e.source = source;
      throw e;
    }

    var template = function(data) {
      return {template: render.call(this, data, _), event: events};
    };

    // Provide the compiled source as a convenience for precompilation.
    var argument = settings.variable || 'obj';
    template.source = 'function(' + argument + '){\n' + source + '}';

    return template;
  };
</script>
<script>
//  _.templateSettings = {
//     evaluate: /<%([\s\S]+?)%>/g,
//     interpolate: /{{([\s\S]+?)}}/g,
//     escape: /<%-([\s\S]+?)%>/g
//   };

var EventManager = {};
var runDomEvent = function(e, key) { 
     if (EventManager[key] && EventManager[key]['func'] && EventManager[key]['ctx'] && EventManager[key]['target'] && EventManager[key]['ctx']['methods']) {

           return EventManager[key]['ctx']['methods'][EventManager[key]['func']].call(EventManager[key]['ctx'], 
           e, EventManager[key]['target'], 
           EventManager[key]['data'] && typeof EventManager[key]['data'].get === 'function' ? EventManager[key]['data'].get() : EventManager[key]['data']);
     }
}


// function clicked() {console.log('dsfasdfd')}  
class Template {
    constructor(options) {
      this.$el = options.$el;
      this.template = options.template;
      this.complied = _.template(this.template);
      this.data = options.data;
      this.methods = options.methods;
      this.dynamic = options.dynamic;
    }

    render(data) { 
       const d = data || this.data; 
       let res;

       if (true === this.dynamic) {
        res = _.template(this.template)(d);
       } else {
        res = this.complied(d);
       }
       const template = res.template;
       const event = res.event;

       this.$innerEl = $(template); 
       this.domEvents = event;

       if (!this.$el) {
          this._initEvents(data);
          return this.$innerEl[0].outerHTML;
       } 
       
       this.$el.html(this.$innerEl[0]);
       this._initEvents();
       return this;
    }

    _initEvents(data) { 
        // console.log(flag, this.domEvents, this.$innerEl[0])
       const that = this;
       this.domEvents.forEach((evt) => {
           const $found = $('<div>' + that.$innerEl[0].outerHTML + '</div>').find('[' + evt.id + ']'); 
           if ($found.length > 0) { 
            //    console.log(flag, that.methods, that.methods[evt.func])
               if (that.methods && that.methods[evt.func]) { 
                   EventManager[evt.id]['target'] = $found;
                   EventManager[evt.id]['ctx'] = that;
                   EventManager[evt.id]['data'] = data;
                //    console.log(evt.id, EventManager[evt.id]);
                //    $found.bind(evt.event, function(e) {
                    //    that.methods[evt.func].call(that, e, $found);
                //    })
               }
           }
       })
    }
}

var makeReactTemplate = function(opts, data) { 

   const t = (new Template({
       $el: opts.$el,
       template: opts.template,
       data: data,
       methods: opts.methods,
       dynamic: opts.dynamic,
   }));

   if (data && opts.$el) {
    makeReactive(data, [function() {
       t.render();
    }]);
   }
  
   return t;
}
var makeReactive = (function() {

 function _makeReactive (
  object,
  key,
  val,
  notifiers
) { 
   const obj = object;

   obj.setterCallback = notifiers && notifiers[0] && typeof notifiers[0] === 'function' ? notifiers[0] : function() {};
   obj.getterCallback = notifiers && notifiers[1] && typeof notifiers[1] === 'function' ? notifiers[1] : function() {};

  const property = Object.getOwnPropertyDescriptor(obj, key)
  if (property && property.configurable === false) {
    return
  }

    if (!obj.set) {
     obj.set = function(key, val) {
              obj[key] = val;
              _makeReactive(obj, key, val, notifiers);
     };
     obj.get = function() {
         let result = [];
         if (Array.isArray(this)) {
            for(let i=0; i<this.length; i++) {
                let item = {};
                Object.keys(this[i]).forEach((k) => { 
                  if (k === 'set' || k === 'get' || k === 'setterCallback' || k === 'getterCallback') return;
                     item[k] = this[i][k]
                })

                result.push(item);
            }
         } else {
            result = {}; 
            Object.keys(this).forEach((k, i) => { 
              if (k === 'set' || k === 'get' || k === 'setterCallback' || k === 'getterCallback') return;
              result[k] = obj[k];
            })
         }
         
         return result;
     }
    }

  Object.defineProperty(obj, key, {
    get: function reactiveGetter () {
      obj.getterCallback.call(obj, key, val);
  
      return val;
    },
    set: function reactiveSetter (newVal) {
        const oldVal = val;
      /* eslint-disable no-self-compare */
      if (newVal === val || (newVal !== newVal && val !== val)) {
        return
      }
     val = newVal
 
    obj.setterCallback.call(obj, key, oldVal, newVal);
    }
  });

  if (val === Object(val)) {
     makeReactive(val, notifiers);
  }
} 
   
   function makeReactive(obj, notifiers) {
    for(const key in obj) {
        _makeReactive(obj, key, obj[key], notifiers);
    }
   }

   return makeReactive;
})();

var makeDomEvent = function(ctx, funcName) {
     if (undefined !== ctx['methods'] && typeof ctx['methods'][funcName] === 'function') {
        return ctx['methods'][funcName].call(ctx);
     }
     return;
}
</script>
</head>
<body>
<div id="app"></div>
<script>

const data = { title: 'foo', children: [{name: 'tintin'}, {name: 'tia'}, {name: 'wynn'}], subject: {today: 'today', yesterday: 'yesterday'}, foo: {bar: {coo: 'coo'}}}

const child = makeReactTemplate({ 
    template: '<h2>{{coo}}</h2>',
}, data.foo.bar)

const li = makeReactTemplate({ 
    template: '<li @click="click">click to delete "{{name}}"</li>',
    dynamic: true,
    methods: {
        click(e, $el, d) {
            console.log('<li>---------</li>', d)
            const children = data.children.get();
            let res = [];
            for(let i=0; i<children.length; i++) {
                if (children[i].name !== d.name) {
                      res.push({name: children[i].name});
                }
            }
            data.set('children', res);
        }
    }
});

makeReactTemplate({
    $el: $('#app'),
    template: `<div>{{ child.render() }}
              <h1 @click="clicked"> today subject: {{data.subject.today}} </h1>
              <h1 @click="click1"> yesterday subject: {{data.subject.yesterday}} </h1>
               <h5>my title: {{data.title}}</h5>
               <ul>
               <% for(var i=0; i<data.children.length; i++) { %>
                   {{ li.render(data.children[i]) }}
               <% } %>
               </ul>
               <button @click="add">+</button>
               </div>`,
    methods:{
        clicked(e, $el) {
            e.preventDefault();
            console.log('clicked')
        },
        click1() {
            console.log('click1')
        },
        add(e, $el) {
            const names = ['tom', 'jon', 'tim', 'tian', 'sandie', 'ben', 'dan', 'don', 'zen', 'lucu'];
            const name = names[Math.floor(Math.random() * Math.floor(10))];
            let res = [];
            for(let i=0; i<data.children.length; i++) {
                res.push({name: data.children[i].name});
            }
            res.push({name});
            data.set('children', res);
        }
    }           
}, data).render();


function notify(key, val) {
    console.log.apply(console.log, [this, key, val]);
}

function setterNotify(key, oldVal, newVal) {
    console.log.apply(console.log, ['set', this, key, oldVal, newVal]);
}

function getterNotify(key, val) {
    console.log.apply(console.log, ['get', this, key, val]);
}


let test = {
    foo: { foofoo: 'foofoo'},
    bar: 'bar',
};

makeReactive(test, [setterNotify]);

// let myPromise = {
//     result: null,
//     pending: true,
// };

// makeReactive(myPromise, [function() {
//      if (this.result !== null) {
//         alert('fire');
//      }
// }]);

// setTimeout(() => {
//     myPromise.result = true;
// }, 2000)

// function service() {
//     return new Promise((resolve) => {
//         // setTimeout(() => {
//         //     resolve(true)
//         // }, 3000)
//         resolve(true)
//     })
// }

// service().then((res) => {
//   if (true === res) {
//       alert('native promise')
//   }
// })

class myPromise {
    constructor(resolveCallback) {
       this.p = {
           result: null
       }; 
       makeReactive(this.p);
       const that = this; 
       const resolve = function(val) {
          setTimeout(() => {
             that.p.result = val;
          }, 0)
       };
       resolveCallback.call(resolveCallback, resolve)
    }

    then(callback) {
        this.p.setterCallback = function(key, val) {
           return callback.call(callback, this.result);
        };
    }
}

function service() {
    return new myPromise((resolve) => {
        setTimeout(() => {
            resolve('yam')
        }, 2000);
    })
}
const p = service();

p.then((res) => {
   if ('freeman' === res) console.log(res + ', my own promise');
})
p.then((res) => {
   if ('yam' === res) console.log(res + ', my own promise');
})

// function service() {
//     let myPromise = {
//        result: null,
//        pending: true,
//     };

//     makeReactive(myPromise);

//     setTimeout(() => {
//        myPromise.result = true;
//     }, 0);
//     // myPromise.result = true;

//     return myPromise;
// }

// service().setterCallback =  function(key, val) {
//      if (this.result === true) alert('my promise: ' + key + ' ' + val);
// };
</script>
</body>
</html>