<!doctype html>
<html>
<head>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.slim.min.js"></script>
<script>
<?php echo file_get_contents(__DIR__ . '/js/jdoh.js'); ?>

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
<h1 id="foo">foo</h1><h1 id="bar">bar</h1>
<script>
const createVApp = (count) => createElement('div', {
  attrs: {
    id: 'app',
    dataCount: count,
  },
  children: [
    createElement('input'),
    String(count),
    ...Array.from({ length: count }, () => createElement('img', {
      attrs: {
        src: 'https://media.giphy.com/media/cuPm4p4pClZVC/giphy.gif',
      },
    })),
  ],
});

let count = 0;
let vApp = createVApp(count); console.log(createVApp)
const $app = render(vApp);

let $rootEl = mount($app, document.getElementById('app'));

// setInterval(() => {
//   const vNewApp = createVApp(Math.floor(Math.random() * 10));
//   const patch = diff(vApp, vNewApp);
//   $rootEl = patch($rootEl); 
//   vApp = vNewApp;
// }, 2000);
const html = "<h1 class=foo>hello </h1><ul><li>foo</li><li>bar</li></ul><form><label for=hello></label><input type=text value=freeman /></form><p style=display:none>asdfs  dfd sasdf ++++</p>";
const $html = $("<div>" + html + "</div>");

const makeVdom = ($el) => {
    const result = [];
    const $children = $el.children(); 

    if ($children.length > 0) {
        $children.each(function() { 
           const attr = this.attributes; 
           result.push({
            tag: this.tagName.toLowerCase(), 
            text: $(this).text(),
            attr: Object.keys(attr).map(index => Object.create({name: attr[index].name, value: attr[index].value})), 
            children: makeVdom($(this))})
        });
    }

    return result;
};

console.log(makeVdom($html));
</script>
</body>
</html>