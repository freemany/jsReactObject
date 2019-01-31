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
})
// service('tintin', 0).then((res) => {
//     console.log(res);
// })
// service('tia', 1000).then((res) => {
//     console.log(res);
// })
// service('jon', 1000)
</script>
</head>
<body>
</body>
</html>