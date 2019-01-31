<html>
<head>
<script>

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
            if (data[k] === v) return;
            oldValue = data[k];
        }
        if (typeof v === 'object') {
            data[k] = makeReactObject(v, cb);  
        } else {
            data[k] = v;
        }
        if (typeof localCb === 'function') {
            data.set = localCb;
        }
        cb.call(data, oldValue, v, data);
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


const data = {title: 'freeman', foo: { bar: 'bar', coo: {last: 'last'}}};
const d = makeObjectReact(data,function(o, n, data) { console.log(o, n, data, this);});

console.log(d.get('title')); d.set('title', 'hello')

</script>
</head>
<body>
</body>
</html>