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

    function _makeVdom($el, instance) {
      const result = [], vd = [];
      const $children = $el.children(); 
      const textNodes = ['h1', 'h2', 'h3', 'h4', 'h5', 'p', 'span', 'li', 'a', 'button'];
  
      if ($children.length > 0) {
          $children.each(function() { 
            const directText = $(this).clone().children().remove().end().text(); 
            const matches = directText.match(p);
            const $that = $(this);
            if (null !== matches) {
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

          // attach dom events
          const attr = this.attributes; 
          const attrs = Object.keys(attr).map((index) => {return {name: attr[index].name, val: attr[index].value}});
   
          attrs.forEach((a, i) => { 
            let matches;
            if ('@keyup' === a.name) {
                 $that.keyup(function(e) {
                     instance.methods[a.val].call(instance, e, $(this))
                 });
                 $(this).removeAttr(a.name);
            }
            if ('@click' === a.name) { 
                 $that.click(function(e) {
                     instance.methods[a.val].call(instance, e, $(this))
                 });
                 $(this).removeAttr(a.name);
            }
            if ('value' === a.name) {
                matches = a.val.match(p);
                if (null !== matches) {
                     $(this).val(eval(matches[1]));
                     $(this).removeAttr(a.name);
                }
            }

            if ('placeholder' === a.name) {
                matches = a.val.match(p);
                if (null !== matches) {
                     $(this).removeAttr(a.name);
                     $(this).attr(a.name, eval(matches[1]));
                }
            }

            if ('@for' === a.name) { 
                const parts = a.val.split('in');
                const list = eval('instance.' + parts[1].trim());
                const $tpl = $that.children().clone();
                const itemPlaceholder = parts[0].trim();
                $that.html('');
                list.forEach((item) => {
                    const $item = $tpl.clone();
                    matches = $item.clone().children().remove().end().text().match(p); 
                    if (null !== matches) {
                         const ph = matches[1].trim(); 
                         $item.html($item.html().replace('{{' +ph+ '}}', ''));
                         eval("const " + itemPlaceholder + "=" + JSON.stringify(item) + ";$item.prepend(document.createTextNode(" + ph + "));");
                    }  
                    $that.append($item);
                });
                _makeVdom($that, instance); 
                $(this).removeAttr(a.name);
            }
          });

          return _makeVdom($(this), instance);
        });
      }
      return $el;
    }; 
  
    function makeVdom(el, instance) {
      const $html = $('<div>' + el.outerHTML + '</div>'); 
      return _makeVdom($html, instance)[0]
    }
  
    return makeVdom;
  
  })();
</script>
</head>
<body>
<div id="app"></div>
<ul></ul>
<input id="add" type="text"><button>+</button>
<script>
class Template {
    constructor(opts) {
        this.template = opts.template;
        this.$html = $(this.template);
        this.$el = opts.$el;
        this.methods = opts.methods;
        this.data = opts.data;
    }
    render() {
        this.$el.append(renderDom(this.$html[0], this));
    }
}
const data = {title: 'freeman', foo: { bar: 'bar', coo: {last: 'last'}}, todo: {list: [{name: 'freeman'}, {name: 'tintin'}]}};
makeObjectReact(data);
const tpl = new Template({
    data: data,
    $el: $('#app'),
    template: `<div class="wrapper" id="app">
                  <h1>{{data.title}}</h1>
                  <h2>{{data.foo.bar}}</h2>
                  <ul @for="item in data.todo.get('list')">
                   <li>{{item.name}}  <button @click="removeItem">-</button></li>
                  </ul>
                  <input type="text" @keyup="updateTitle" value="{{data.title}}">
                  <input type="text" @keyup="updateBar" placeholder="{{data.foo.bar}}">
               </div>`,
    methods: {
        updateTitle(e, $el) {
            console.log('updating title....');
            data.set('title', $el.val());
        },
        updateBar(e, $el) {
            console.log('updating bar....');
            data.foo.set('bar', $el.val());
        },
        removeItem(e, $el) {
            $el.parent().remove();
        }
    }           
});
tpl.render();

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