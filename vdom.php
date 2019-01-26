<!doctype html>
<html>
<head>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.slim.min.js"></script>
<script>
const createElement = (tagName, { attrs = {}, children = [] } = {}) => {
  return {
    tagName,
    attrs,
    children,
  };
};

const zip = (xs, ys) => {
  const zipped = [];
  for (let i = 0; i < Math.max(xs.length, ys.length); i++) {
    zipped.push([xs[i], ys[i]]);
  }
  return zipped;
};

const diffAttrs = (oldAttrs, newAttrs) => {
  const patches = [];

  // set new attributes
  for (const [k, v] of Object.entries(newAttrs)) { 
    patches.push($node => {
      $node.setAttribute(k, v);
      return $node;
    });
  }

  // remove old attributes
  for (const k in oldAttrs) {
    if (!(k in newAttrs)) {
      patches.push($node => {
        $node.removeAttribute(k);
        return $node;
      });
    }
  }

  return $node => {
    for (const patch of patches) {
      patch($node);
    }
  };
};

const diffChildren = (oldVChildren, newVChildren) => {
  const childPatches = [];
  oldVChildren.forEach((oldVChild, i) => {
    childPatches.push(diff(oldVChild, newVChildren[i]));
  });

  const additionalPatches = [];
  for (const additionalVChild of newVChildren.slice(oldVChildren.length)) {
    additionalPatches.push($node => {
      $node.appendChild(render(additionalVChild));
      return $node;
    });
  }

  return $parent => {
    for (const [patch, child] of zip(childPatches, $parent.childNodes)) {
      patch(child);
    }

    for (const patch of additionalPatches) {
      patch($parent);
    }

    return $parent;
  };
};

const renderElem = ({ tagName, attrs, children }) => {
  const $el = document.createElement(tagName);

  if (attrs.text) {
    const text = document.createTextNode(attrs.text);
    delete(attrs.text);
    $el.appendChild(text);
  }

  // set attributes
  for (const [k, v] of Object.entries(attrs)) {
    $el.setAttribute(k, v);
  }

  // set children
  for (const child of children) {
    const $child = render(child);
    $el.appendChild($child);
  }

  return $el;
};

const render = (vNode) => {
  if (typeof vNode === 'string') {
    return document.createTextNode(vNode);
  }

  return renderElem(vNode);
}

const diff = (vOldNode, vNewNode) => {
  if (vNewNode === undefined) {
    return $node => {
      $node.remove();
      return undefined;
    };
  }

  if (typeof vOldNode === 'string' ||
    typeof vNewNode === 'string') {
    if (vOldNode !== vNewNode) {
      return $node => {
        const $newNode = render(vNewNode);
        $node.replaceWith($newNode);
        return $newNode;
      };
    } else {
      return $node => undefined;
    }
  }

  if (vOldNode.tagName !== vNewNode.tagName) {
    return $node => {
      const $newNode = render(vNewNode);
      $node.replaceWith($newNode);
      return $newNode;
    };
  }

  const patchAttrs = diffAttrs(vOldNode.attrs, vNewNode.attrs);
  const patchChildren = diffChildren(vOldNode.children, vNewNode.children);
  
  return $node => { 
    patchAttrs($node);
    patchChildren($node); 
    return $node;
  };
};

const mount = ($node, $target) => {
  $target.replaceWith($node);
  return $node;
};
</script>
</head>
<body>
<div id="app"></div>
<script>

const makeVdom = (function(createElement) {
  if (typeof createElement !== 'function') {
      throw new Error('makeVdom depends on createElement but createElement is not available');
  }
  function _makeVdom($el) {
    const result = [], vd = [];
    const $children = $el.children(); 
    const textNodes = ['h1', 'h2', 'h3', 'h4', 'h5', 'p', 'span', 'li', 'a', 'button'];

    if ($children.length > 0) {
        $children.each(function() { 
           const attr = this.attributes; 
           const items = Object.keys(attr).map(index => Object.create({name: attr[index].name, val: attr[index].value}))
           const attrs = {};
           items.forEach((i) => {
                attrs[i.name] = i.val;
           });
           const tagName = this.tagName.toLowerCase();
           const directText = $(this).clone().children().remove().end().text();
           if (textNodes.indexOf(tagName) > -1 && directText) { 
               attrs['text'] = directText;
           }
           const el = [
            tagName, {
            attrs: attrs, 
            children: _makeVdom($(this))
           }];
           result.push(createElement.apply(createElement, el)); 
        });
    }

    return result;
  };

  function makeVdom(el) {
    const $html = $('<div>' + el.outerHTML + '</div>'); 
    return _makeVdom($html)[0]
  }

  return makeVdom;

})(createElement);

<?php echo file_get_contents(__DIR__ . '/js/jdoh-vdom.js'); ?>

// let vApp = makeVdom(html); 
// const $app = render(vApp);

// let $rootEl = mount($app, document.getElementById('app'));

/** Todo app here */
const todoData = {title: 'My todo app', list: [{id: uuid(), name: 'wash', done: '', editting: false}, {id: uuid(), name: 'homework', done: '', editting: false}], newValue: ''};

const Title = makeReactTemplate({ 
    template: '<h2>{{title}}</h2>',
}, todoData)

const li = makeReactTemplate({ 
    template: `<li class="item {{done}}">
              <% if (editting === false) { %>
               <span>{{name}}</span> 
              <% } else { %>
                <input type="text" value="{{name}}" onfocus="this.select()" jd-model="edittingItem" >
              <% } %>  
              <button @click="delete">-</button>
              <button @click="makeDone">{{done === "" ? "done" : "undone"}}</button>
              <button @click="startEdit">{{editting === false ? "edit" : "save"}}</button></li>`,
    dynamic: true,
    methods: {
        startEdit(e, el, item) {
            e.preventDefault();

            const list = todoData.list.get();
            for(let i=0; i < list.length; i++) {
                if (list[i].id === item.id) {
                    if (true === list[i].editting) {
                        list[i].editting = false;
                        // const value = $(el).prev().prev().prev().val();
                        const value = this.edittingItem; 
                        list[i].name = value;
                    } else {
                        list[i].editting = true;
                    }
                } 
            }
            todoData.set('list', list);
        },
        makeDone(e, el, item) {
            e.preventDefault();

            console.log('todo done', item)
            const list = todoData.list.get();
            let res = [];
            for(let i=0; i < list.length; i++) {
                let done = list[i].done;
                if (list[i].id === item.id) {
                      done = done === '' ? 'done' : '';
                } 
                res.push({id: list[i].id, name: list[i].name, done: done, editting: false});
            }
            todoData.set('list', res);
        },
        delete(e, el, item) {
            e.preventDefault();

            const list = todoData.list.get();
            console.log('delete', item, list)
            let res = [];
            for(let i=0; i < list.length; i++) {
                if (list[i].id !== item.id) {
                      res.push(list[i]);
                }
            }
            todoData.set('list', res);
        }
    }
});

makeReactTemplate({
    $el: $('#app'),
    template: `<div id='app'>
              {{ Title.render() }}
               <ul>
               <% for(var i=0; i < todoData.list.length; i++) { %>
                   {{ li.render(todoData.list[i]) }}
               <% } %>
               </ul>
               <input type='text' value="{{todoData.newValue}}" jd-model="newItem" ><button @click="add">+</button>
               </div>`,
    methods:{
        add(e, el) { 
            e.preventDefault();
            const name = this.newItem;
            let res = typeof todoData.list.get === 'function' ? todoData.list.get() : [];
            const newItem = {name: name, id: uuid(), done: '', editting: false};
            res.push(newItem);
            console.log('add', newItem);
            todoData.newValue = '';
            todoData.set('list', res);
        }
    }           
}, todoData).render();
</script>
</body>
</html>