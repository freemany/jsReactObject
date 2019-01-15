<!doctype html>
<html>
<head>
<script>
function _my_setter(obj, key, val) {
    _makeReactive(obj, key, val);
}

function makeReactive(obj, notifiers) {
    for(const key in obj) {
        _makeReactive(obj, key, obj[key], notifiers);
    }
}
/**
 * Define a reactive property on an Object.
 */
function notify(key, val) {
    console.log.apply(console.log, [this, key, val]);
}

function setterNotify(key, oldVal, newVal) {
    console.log.apply(console.log, ['set', this, key, oldVal, newVal]);
}

function getterNotify(key, val) {
    console.log.apply(console.log, ['get', this, key, val]);
}

function _makeReactive (
  object,
  key,
  val,
  notifiers
//   customSetter,
//   shallow
) { 
   const obj = object;

   obj.setterCallback = notifiers && notifiers[0] && typeof notifiers[0] === 'function' ? notifiers[0] : function() {};
   obj.getterCallback = notifiers && notifiers[1] && typeof notifiers[1] === 'function' ? notifiers[1] : function() {};
//   const dep = new Dep()

  const property = Object.getOwnPropertyDescriptor(obj, key)
  if (property && property.configurable === false) {
    return
  }

  // cater for pre-defined getter/setters
//   const getter = property && property.get
//   const setter = property && property.set
//   if ((!getter || setter) && arguments.length === 2) {
//     val = obj[key]
//   }

//   if (!obj.$set) {
//      Object.defineProperty(obj, '$set', { 
//          value: function(key, val) {
//                 _my_setter(obj, key, val);
//      }});
//   }

    if (!obj.set) {
     obj.set =  function(key, val) {
                _my_setter(this, key, val);
     };
     obj.get = function() {
         let result = [];
         Object.keys(this).forEach((k, i) => {
              if (k === 'set' || k === 'get') return;
              result[k] = obj[k];
         })

         return result;
     }
    }

//   let childOb = !shallow && observe(val)
  Object.defineProperty(obj, key, {
    // enumerable: true,
    // configurable: true,
    get: function reactiveGetter () {
    //   const value = val
    //   if (Dep.target) {
    //     dep.depend()
    //     if (childOb) {
    //       childOb.dep.depend()
    //       if (Array.isArray(value)) {
    //         dependArray(value)
    //       }
    //     }
    //   }
 
      obj.getterCallback.call(obj, key, val);
  
      return val;
    },
    set: function reactiveSetter (newVal) {
        const oldVal = val;
      /* eslint-disable no-self-compare */
      if (newVal === val || (newVal !== newVal && val !== val)) {
        return
      }
      /* eslint-enable no-self-compare */
    //   if (process.env.NODE_ENV !== 'production' && customSetter) {
    //     customSetter()
    //   }
      // #7981: for accessor properties without setter
    //   if (getter && !setter) return
    //   if (setter) {
    //     setter.call(obj, newVal)
    //   } else {
        val = newVal
    //   }
    //   childOb = !shallow && observe(newVal)
    //   dep.notify()
    // notify('set ', obj, key, value)
  
    obj.setterCallback.call(obj, key, oldVal, newVal);
  
    }
  });
}
</script>
</head>
<body>
<script>
let test = {
    foo: 'foo',
    bar: 'bar',
};

makeReactive(test, [setterNotify, getterNotify]);

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