<html>
<head>
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

function service(val, timeout) {
    return new P((resolve) => {
        setTimeout(() => {
            resolve(val)
        }, timeout);
    })
}

service('freeman', 2000).then((res) => {
    console.log(res);
}).then((res) => {
    console.log('second time', res);
}).then((res) => {
    console.log('3rd time', res);
})
service('tintin', 0).then((res) => {
    console.log(res);
})
service('tia', 1000).then((res) => {
    console.log(res);
})
service('jon', 1000);

// all
P.all([
    service('1tintin', 2000), 
    service('2tia', 1000),
    service('3tia', 4000),
    service('4tia', 2000)])
.then((arrRes) => {
    console.log(arrRes);
});

// race
P.race([
    service('1tintin', 2000), 
    service('2tia', 1000),
    service('3tia', 4000),
    service('4tia', 500)])
.then((res) => {
    console.log(res, 'assert:', res === '4tia');
});

// resolve
function promise() {
    return new Promise((resolve) => {
        setTimeout(() => {
            resolve('native promise');
        }, 3000);
    })
}
P.resolve(promise()).then((res) => {
  console.log(res);
});
P.resolve('resolve string from P.resolve').then((res) => {
  console.log(res);
});
</script>
</head>
<body>
</body>
</html>