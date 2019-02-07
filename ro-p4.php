<html>
<head>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.slim.min.js"></script>
<script>
const CallbackManager = (() => {

    const _cb = {};
    const _$el = {}; 
    const render = (key, value, domBuilder, cb, data) => { 
       _cb[key] = cb;
       
       _$el[key] = domBuilder();
       cb.call(cb, null, value, _$el[key]);
       data.setterCallback = _attach(key);
   
       return _$el[key];
    };

    const _attach = (key) => { 
       return function(o, n) {
           return _cb[key].call(_cb[key], o, n, _$el[key]);
       }    
    };

    return {
       render: render,
    }
})();

const makeObjectReact = (() => {

   const removeGetSet = (d) => {
   const data = JSON.parse(JSON.stringify(d));
   delete(data.set);
   delete(data.get);
   delete(data.setterCallback);

   for(const k in data) {
        if (typeof data[k] === 'object') {
            data[k] = removeGetSet(data[k]);
        }
    }
   return data;
}

const makeReactObject = (data, cb) => {
    data.get = (k) => {
        if (data[k]) {
            return removeGetSet(data[k]);
        }
    };

    data.set = (k, v, localCb) => { 
        let oldValue;
        if (data[k]) {
            if (typeof v === 'string' && data[k] === v) return;
            oldValue = data[k];
        }
        if (typeof v === 'object') {  
            data[k] = makeReactObject(v, cb);  
        } else {
            data[k] = v;
        }
      
        if (undefined == data.setterCallback) {
            if (typeof localCb === 'function') {
               data.setterCallback = localCb;
            } else {
               data.setterCallback = cb;
            }
        }
      
        data.setterCallback.call(data, oldValue, v, data);
    };

    for(const k in data) {
        if (typeof data[k] === 'object') {
            data[k] = makeReactObject(data[k], cb);
        }
    }

    return data;
  };

  return makeReactObject;
})();

const renderDom = (function() {
    const p = /{{([\s\S]+?)}}/;

    function _makeVdom($el) {
      const result = [], vd = [];
      const $children = $el.children(); 
      const textNodes = ['h1', 'h2', 'h3', 'h4', 'h5', 'p', 'span', 'li', 'a', 'button'];
  
      if ($children.length > 0) {
          $children.each(function() { 
            const directText = $(this).clone().children().remove().end().text(); 
            const matches = directText.match(p);
            if (null !== matches) {
                const $that = $(this);
                const content = matches[1].trim();
                const parentObj = content.split('.');
                parentObj.pop();
                const $title = CallbackManager.render(content, eval(content), 
                                                      function() {
                                                         return $that;
                                                      },
                                                      function(o, n, $el) {
                                                           $el.text(n);
                                                     }, eval(parentObj.join('.')));
          };
          return _makeVdom($(this));
        });
      }
      return $el;
    }; 
  
    function makeVdom(el) {
      const $html = $('<div>' + el.outerHTML + '</div>'); 
      return _makeVdom($html)[0]
    }
  
    return makeVdom;
  
  })();
</script>
</head>
<body>
<div id="app"></div>
<input id="title" type="text">
<input id="bar" type="text">
<input id="last" type="text">
<ul></ul>
<input id="add" type="text"><button>+</button>
<script>
class Template {
    constructor(opts) {
        this.template = opts.template;
        this.$html = $(this.template);
        this.$el = opts.$el;
    }
    render() {
        this.$el.append(renderDom(this.$html[0]));
    }
}
const data = {title: 'freeman', foo: { bar: 'bar', coo: {last: 'last'}}, list: [{name: 'freeman'}, {name: 'tintin'}]};

const tpl = new Template({
    $el: $('#app'),
    template: `<div class="wrapper" id="app"><h1>{{data.title}}</h1><h2>{{data.foo.bar}}</h2></div>`,
});
tpl.render();

makeObjectReact(data);

// // building vdom
// const $title = CallbackManager.render('data.title', data.title, 
// function() {
//     return $('<h1>');
// },
// function(o, n, $el) {
//      $el.text(n);
// });
// const $bar = CallbackManager.render('data.foo.bar', data.foo.bar, 
// function() {
//     return $('<h2>');
// },
// function(o, n, $el) {
//      $el.text(n);
// });
// const $last = CallbackManager.render('data.foo.coo.last', data.foo.coo.last, 
// function() {
//     return $('<h3>');
// },
// function(o, n, $el) {
//      $el.text(n);
// });
// const $ul = CallbackManager.render('data.list', data.list, 
// function() { 
//        return $('ul');
// },
// function(o, n, $el) {
//         $el.empty();
//         data.get('list').forEach((x, i) => { 
//             const $li = $('<li>');
//             $li.text(x.name)
//                .click(function() {
//                 const list = data.get('list'); 
//                 list.splice(i, 1);
//                 data.set('list', list, CallbackManager.attach('data.list'));
//            })
//            $el.append($li);
//        })
// });

// $('#app').append($title).append($bar).append($last).append($ul);

$('#title').keyup(function() {
    data.set('title', $(this).val());
    //  data.set('title', $(this).val(), CallbackManager.attach('data.title'));
});
$('#bar').keyup(function() {
    data.foo.set('bar', $(this).val());
});
// $('#last').keyup(function() {
//     data.foo.coo.set('last', $(this).val(), CallbackManager.attach('data.foo.coo.last'));
// });
// $('button').click(function() {
//      const name = $('#add').val();
//      const list = data.get('list');  
//      list.push({name}); 
//      data.set('list', list, CallbackManager.attach('data.list'));
// });     
</script>
</body>
</html>