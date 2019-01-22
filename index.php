<!doctype html>
<html>
<head>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.9.1/underscore.js"></script>
<script>
 _.templateSettings = {
    evaluate: /<%([\s\S]+?)%>/g,
    interpolate: /{{([\s\S]+?)}}/g,
    escape: /<%-([\s\S]+?)%>/g
  };
class Template {
    constructor(options) {
      this.$el = options.$el;
      this.template = options.template;
      this.complied = _.template(this.template);
      this.data = options.data;
    }

    render(data) { 

       const d = data || this.data; 
       if (!this.$el) {
          return this.complied(d);
       } 
       this.$el.html(this.complied(d));
       return this;
    }
}

var makeReactTemplate = function(opts, data) { 

   const t = (new Template({
       $el: opts.$el,
       template: opts.template,
       data: data,
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
         Object.keys(this).forEach((k, i) => {
              if (k === 'set' || k === 'get' || k === 'setterCallback' || k === 'getterCallback') return;
              result[k] = obj[k];
         })

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
</script>
</head>
<body>
<div id="app"></div>
<script>

const data = { title: 'foo', children: [{name: 'tintin'}, {name: 'tia'}, {name: 'wynn'}], subject: {today: 'today', yesterday: 'yesterday'}, foo: {bar: {coo: 'coo'}}}

// const v = (new Template({
//     $el: $('#test'),
//     data: data,
//     template: `<h5>my title: {{data.title}}</h5>
//                <% for(var i=0; i<data.children.length; i++) { %>
//                    <p>{{ data.children[i] }}</p>
//                <% } %>`,
// })).render();

// makeReactive(data, [function() {
//     v.render();
// }]);


const child = makeReactTemplate({ 
    template: '<h2>{{coo}}</h2>',
}, data.foo.bar)

const li = makeReactTemplate({ 
    template: '<li>{{name}}</li>',
});

makeReactTemplate({
    $el: $('#app'),
    template: `{{ child.render() }}
              <h1> today subject: {{data.subject.today}} </h1>
              <h1> yesterday subject: {{data.subject.yesterday}} </h1>
               <h5>my title: {{data.title}}</h5>
               <ul>
               <% for(var i=0; i<data.children.length; i++) { %>
                   {{ li.render(data.children[i]) }}
               <% } %>
               </ul>`,
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
   if ('freeman' === res) alert(res + ', my own promise');
})
p.then((res) => {
   if ('yam' === res) alert(res + ', my own promise');
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