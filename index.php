<!doctype html>
<html>
<head>
<script>
function makeReactive(obj, notifiers) {
    for(const key in obj) {
        _makeReactive(obj, key, obj[key], notifiers);
    }
}

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
</script>
</head>
<body>
<script>
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