<!doctype html>
<html>
<head>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.slim.min.js"></script>
<script>
var uuid = function() {
    return Math.random().toString(36).substring(2) + (new Date()).getTime().toString(36);
};

// By default, Underscore uses ERB-style template delimiters, change the
  // following template settings to use alternative delimiters.
var _ = _ || {};
  _.templateSettings = {
    evaluate: /<%([\s\S]+?)%>/g,
    interpolate: /{{([\s\S]+?)}}/g,
    escape: /<%-([\s\S]+?)%>/g,
    clickEvent: /@click="([\s\S]+?)"/g,
    keyupEvent: /@keyup="([\s\S]+?)"/g,
    dom: /#([\s\S]+?)#/g,
    model: /jd-model="([\s\S]+?)"/g,
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
      (settings.clickEvent || noMatch).source,
      (settings.keyupEvent || noMatch).source,
      (settings.dom || noMatch).source,
      (settings.model || noMatch).source
    ].join('|') + '|$', 'g');

    // Compile the template source, escaping string literals appropriately.
    var index = 0;
    var source = "__p+='";
    text.replace(matcher, function(match, escape, interpolate, evaluate, clickEvent, keyupEvent, dom, model, offset) {
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
        const add = token + " data-event-key=" + token + " data-event onClick=runDomEvent(this,event,\"" + token + "\")";
        source +=  add;  
        events.push({id: token, event: 'click', func: clickEvent});
        EventManager[token] = {event: 'click', func: clickEvent};
      } else if (keyupEvent) { 
        const token = "jd-" + String(Math.random()).substr(7);
        const add = token + " data-event-key=" + token + " data-event onkeyup=runDomEvent(this,event,\"" + token + "\")";
        source +=  add;  
        events.push({id: token, event: 'keyup', func: keyupEvent});
        EventManager[token] = {event: 'keyup', func: keyupEvent};
      } else if (dom) { 
        const token = "jd-" + String(Math.random()).substr(7);
        source +=  token;  
        DomManager[token] = dom;
      } else if (model) { 
        const token = "jd-" + String(Math.random()).substr(7);
        source +=  token + " oninput=runModelEvent(this,event,\"" + token + "\")";;  
        ModelManager[token] = {key: model};
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

var ModelManager = {};
var EventManager = {};
var DomManager = {};    
var runDomEvent = function(el, e, key) { 
     if (EventManager[key] && EventManager[key]['func'] && EventManager[key]['ctx'] && EventManager[key]['target'] && EventManager[key]['ctx']['methods']) {

           return EventManager[key]['ctx']['methods'][EventManager[key]['func']].call(EventManager[key]['ctx'], 
           e, el, 
           EventManager[key]['data'] && typeof EventManager[key]['data'].get === 'function' ? EventManager[key]['data'].get() : EventManager[key]['data']);
     }
};

var runModelEvent = function(el, e, key) {
    if (ModelManager[key]) { 
        ModelManager[key]['ctx'][ModelManager[key]['key']] = el.value;
    }
}

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
          this._pickupDom();
          this._initEvents(data);
          this._initModel();
          return this.$innerEl[0].outerHTML;
       } 
       
       this.$el.html(this.$innerEl[0]);
       this._initEvents();
       this._pickupDom();
       this._initModel();
       return this;
    }

    _pickupDom() {
       for(const key in DomManager) {
        const $found = $('<div>' + this.$innerEl[0].outerHTML + '</div>').find('[' + key + ']'); 
        if ($found.length > 0) {
              this[DomManager[key]] = function() {
                  return $('body').find('[' + key + ']');
              }; 
        }
       }
    }

    _initModel() {
        for(const key in ModelManager) {
        const $found = $('<div>' + this.$innerEl[0].outerHTML + '</div>').find('[' + key + ']'); 
        if ($found.length > 0 && undefined === ModelManager[key]['ctx']) { 
              ModelManager[key]['ctx'] = this;
        }
       }
    }

    _initEvents(data) { 
       const that = this;
       this.domEvents.forEach((evt) => {
           const $found = $('<div>' + that.$innerEl[0].outerHTML + '</div>').find('[' + evt.id + ']'); 
           if ($found.length > 0) { 
               if (that.methods && that.methods[evt.func]) { 
                   EventManager[evt.id]['target'] = that.$innerEl;
                   EventManager[evt.id]['ctx'] = that;
                   EventManager[evt.id]['data'] = data;
               }
           }
       })
    }
}

var makeReactTemplate = function(opts, data) { 

   const t = new Template({
       $el: opts.$el,
       template: opts.template,
       data: data,
       methods: opts.methods,
       dynamic: opts.dynamic,
   });

   if (data && opts.$el) {
    makeReactive(data, [function() {
       t.render();
    }]);
   }
  
   return t;
};

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
})()
</script>
<style>
.done {
    text-decoration: line-through;
}
</style>
</head>
<body>
<div id="app"></div>
<script>

const todoData = {title: 'My todo app', list: [{id: uuid(), name: 'wash', done: '', editting: false}, {id: uuid(), name: 'homework', done: '', editting: false}], newValue: ''};

const Title = makeReactTemplate({ 
    template: '<h2>{{title}}</h2>',
}, todoData)

const li = makeReactTemplate({ 
    template: `<li class="item {{done}}">
              <% if (editting === false) { %>
               <span>{{name}}</span> 
              <% } else { %>
                <input type="text" value="{{name}}" onfocus="this.select()" jd-model="edittingItem" >
              <% } %>  
              <button @click="delete">-</button>
              <button @click="makeDone">{{done === "" ? "done" : "undone"}}</button>
              <button @click="startEdit">{{editting === false ? "edit" : "save"}}</button></li>`,
    dynamic: true,
    methods: {
        startEdit(e, el, item) {
            e.preventDefault();

            const list = todoData.list.get();
            for(let i=0; i < list.length; i++) {
                if (list[i].id === item.id) {
                    if (true === list[i].editting) {
                        list[i].editting = false;
                        // const value = $(el).prev().prev().prev().val();
                        const value = this.edittingItem; 
                        list[i].name = value;
                    } else {
                        list[i].editting = true;
                    }
                } 
            }
            todoData.set('list', list);
        },
        makeDone(e, el, item) {
            e.preventDefault();

            console.log('todo done', item)
            const list = todoData.list.get();
            let res = [];
            for(let i=0; i < list.length; i++) {
                let done = list[i].done;
                if (list[i].id === item.id) {
                      done = done === '' ? 'done' : '';
                } 
                res.push({id: list[i].id, name: list[i].name, done: done, editting: false});
            }
            todoData.set('list', res);
        },
        delete(e, el, item) {
            e.preventDefault();

            const list = todoData.list.get();
            console.log('delete', item, list)
            let res = [];
            for(let i=0; i < list.length; i++) {
                if (list[i].id !== item.id) {
                      res.push(list[i]);
                }
            }
            todoData.set('list', res);
        }
    }
});

makeReactTemplate({
    $el: $('#app'),
    template: `<div>
              {{ Title.render() }}
               <ul>
               <% for(var i=0; i < todoData.list.length; i++) { %>
                   {{ li.render(todoData.list[i]) }}
               <% } %>
               </ul>
               <input type='text' value="{{todoData.newValue}}" #new jd-model="newItem" ><button @click="add">+</button>
               </div>`,
    methods:{
        add(e, el) { 
            e.preventDefault();
            // const name = this.new().val();
            const name = this.newItem;
            let res = typeof todoData.list.get === 'function' ? todoData.list.get() : [];
            const newItem = {name: name, id: uuid(), done: '', editting: false};
            res.push(newItem);
            console.log('add', newItem);
            todoData.newValue = '';
            todoData.set('list', res);
        }
    }           
}, todoData).render();
</script>
</body>
</html>