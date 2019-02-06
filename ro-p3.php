<html>
<head>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.slim.min.js"></script>
<script>
class P {
    constructor(resolveCallback) {
       this.cbStack = [];

       const resolve= (result) => {
              this.result = result;
              for(let i=0; i< this.cbStack.length; i++) {
                if (typeof this.cbStack[i] === 'function') {
                     this.cbStack[i].call(this, this.result);
                }
              }    
       }
       resolveCallback.call(resolveCallback, resolve)
    }

    then(cb) {
       this.cbStack.push(cb); 
       return this;
    }
}
P.all = (arrP) => {
    const maxTimeout = 10000;

    if (!Array.isArray(arrP) || arrP.length === 0) {
        throw new Error('Invalid array of promise');
    }
    arrP.forEach((p) => {
        if (typeof p.then !== 'function') {
            throw new Error('Invalid array of promise');
        }
    })

    return new P((resolve) => {
        const pTotal = arrP.length;
        const arrRes = [];
        let isTimeout = false;

        let timer = setTimeout(() => {
           isTimeout = true; 
           resolve(arrRes);
        }, maxTimeout);

        for(let i=0; i < pTotal; i++) { 
           if (true === isTimeout) break; 
            arrP[i].then((res) => {
             if (true === isTimeout) return;  
             arrRes[i] = res; 
             if (Object.keys(arrRes).length === pTotal) {
                   clearTimeout(timer);
                   timer = null;
                   resolve(arrRes);
             }
           });
       } 
    });
};
P.race = (arrP) => {
    const maxTimeout = 10000;

    if (!Array.isArray(arrP) || arrP.length === 0) {
        throw new Error('Invalid array of promise');
    }
    arrP.forEach((p) => {
        if (typeof p.then !== 'function') {
            throw new Error('Invalid array of promise');
        }
    })

    return new P((resolve, reject) => {
        const pTotal = arrP.length;
        let isTimeout = false;
        let hasResult = false;

        let timer = setTimeout(() => {
           isTimeout = true; 
           const error = {error: 'timeout'};
           console.warn(error);
           if (typeof reject == 'function') {
               reject.call(this, error);
           };
        }, maxTimeout);

        for(let i=0; i < pTotal; i++) { 
           if (true === isTimeout) break; 
            arrP[i].then((res) => {
             if (true === isTimeout || true === hasResult) return;  
             clearTimeout(timer);
             timer = null;
             hasResult = true;
             resolve(res);
           });
       } 
    });
};
P.resolve = (p) => {
    const resolver = function(cb) {
         if (typeof p.then === 'function') {
             p.then((res) => {
                cb.call(cb, res);
             });
         } else {
            cb.call(cb, p);
         }
    }

    return {
        then: resolver
    }
};

const CallbackManager = (() => {

    const _cb = {};
    const _$el = {}; 
    const render = (key, value, domBuilder, cb) => {
       _cb[key] = cb;
       
       _$el[key] = domBuilder();
       cb.call(cb, null, value, _$el[key]);

       return _$el[key];
    };

    const attach = (key) => { 
       return function(o, n) {
           return _cb[key].call(_cb[key], o, n, _$el[key]);
       }    
    };

    return {
       render: render,
       attach: attach,
    }
})();

const makeObjectReact = (() => {

   const removeGetSet = (d) => {
   const data = JSON.parse(JSON.stringify(d));
   delete(data.set);
   delete(data.get);

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
        if (typeof localCb === 'function') {
            data.setterCallback = localCb;
        } else {
            data.setterCallback = cb;
        }
        // todo remove
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
const data = {title: 'freeman', foo: { bar: 'bar', coo: {last: 'last'}}, list: [{name: 'freeman'}, {name: 'tintin'}]};

makeObjectReact(data);

// building vdom
const $title = CallbackManager.render('data.title', data.title, 
function() {
    return $('<h1>');
},
function(o, n, $el) {
     $el.text(n);
});
const $bar = CallbackManager.render('data.foo.bar', data.foo.bar, 
function() {
    return $('<h2>');
},
function(o, n, $el) {
     $el.text(n);
});
const $last = CallbackManager.render('data.foo.coo.last', data.foo.coo.last, 
function() {
    return $('<h3>');
},
function(o, n, $el) {
     $el.text(n);
});
const $ul = CallbackManager.render('data.list', data.list, 
function() { 
       return $('ul');
},
function(o, n, $el) {
        $el.empty();
        data.get('list').forEach((x, i) => { 
            const $li = $('<li>');
            $li.text(x.name)
               .click(function() {
                const list = data.get('list'); 
                list.splice(i, 1);
                data.set('list', list, CallbackManager.attach('data.list'));
           })
           $el.append($li);
       })
});

$('#app').append($title).append($bar).append($last).append($ul);

$('#title').keyup(function() {
     data.set('title', $(this).val(), CallbackManager.attach('data.title'));
});
$('#bar').keyup(function() {
    data.foo.set('bar', $(this).val(), CallbackManager.attach('data.foo.bar'));
});
$('#last').keyup(function() {
    data.foo.coo.set('last', $(this).val(), CallbackManager.attach('data.foo.coo.last'));
});
$('button').click(function() {
     const name = $('#add').val();
     const list = data.get('list');  
     list.push({name}); 
     data.set('list', list, CallbackManager.attach('data.list'));
});     
</script>
</body>
</html>