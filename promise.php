<!doctype html>
<html>
<head>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.slim.min.js"></script>
<script>
<?php echo file_get_contents(__DIR__ . '/js/utils.js'); ?>
<?php echo file_get_contents(__DIR__ . '/js/vdom.js'); ?>
<?php echo file_get_contents(__DIR__ . '/js/jdoh-vdom.js'); ?>
</script>
</head>
<body>
<script>

class myPromise {
    constructor(resolveCallback) {
       this.p = {
           result: undefined
       }; 
       makeReactObject(this.p);
       const that = this; 
       const resolve = function(val) {
          setTimeout(() => {
             that.p.result = val;
          }, 0)
       };
       resolveCallback.call(resolveCallback, resolve)
    }

    then(callback) {
        this.p.setterCallback = function(obj, key, oldVal, newVal) {
           return callback.call(callback, this.result);
        };
    }
}

myPromise.all = (arrP) => {
    const maxTimeout = 10000;

    if (!Array.isArray(arrP) || arrP.length === 0) {
        throw new Error('Invalid array of promise');
    }
    arrP.forEach((p) => {
        if (typeof p.then !== 'function') {
            throw new Error('Invalid array of promise');
        }
    })

    const then = function(cb) {
        const p = {
           result: undefined,
        };

        let isTimeout = false;

         setTimeout(() => {
            isTimeout = true;
            p.result = arrRes;
         }, maxTimeout);
    
        const pTotal = arrP.length;
        const arrRes = {};

        makeReactObject(p, [function() {
            cb.call(cb, true === isTimeout ? this.result : Object.values(this.result));
        }]);

       for(let i=0; i < pTotal; i++) { 
          arrP[i].then((res) => {
             arrRes[i] = res; 
             if (Object.keys(arrRes).length === pTotal) {
                   p.result = arrRes;
             }
          });
       } 
       callback = cb;
   }

   return {
       then: then
   }
};

myPromise.race = (arrP) => {
    const maxTimeout = 10000;
    let pTotal = arrP.length;

    if (!Array.isArray(arrP) || arrP.length === 0) {
        throw new Error('Invalid array of promise');
    }
    arrP.forEach((p) => {
        if (typeof p.then !== 'function') {
            throw new Error('Invalid array of promise');
        }
    })

    const then = function(cb) {
        const p = {
           result: undefined,
        };

        let isTimeout = false;

        let res;

         setTimeout(() => {
            isTimeout = true;
            p.result = {result: res, error: 'timeout'};
         }, maxTimeout);
    

        makeReactObject(p, [function() {
            cb.call(cb, this.result);
        }]);

       for(let i=0; i < pTotal; i++) { 
          arrP[i].then((res) => {
             p.result = res;
             p.setterCallback = () => {}; // no following callbacks
          });
       } 
       callback = cb;
   }

   return {
       then: then
   }
};


myPromise.resolve = function(p) {
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
}

function service(val, timeout) {
    return new myPromise((resolve) => {
        setTimeout(() => {
            resolve(val)
        }, timeout);
    })
}

service('freeman', 1000).then((res) => {
   if ('freeman' === res) console.log(res + ', my own promise');
})

myPromise.all([
    service('1tintin', 2000), 
    service('2tia', 1000),
    service('3tia', 4000),
    service('4tia', 2000)])
.then((arrRes) => {
    console.log(arrRes);
});

myPromise.race([
    service('1tintin', 2000), 
    service('2tia', 1000),
    service('3tia', 4000),
    service('4tia', 500)])
.then((res) => {
    console.log(res, 'assert:', res === '4tia');
});

function promise() {
    return new Promise((resolve) => {
        setTimeout(() => {
            resolve('native promise');
        }, 3000);
    })
}

myPromise.resolve(promise()).then((res) => {
  console.log(res);
});
myPromise.resolve('resolve string').then((res) => {
  console.log(res);
});
</script>
</body>
</html>