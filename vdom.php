<!doctype html>
<html>
<head>
<style>
.done {
    text-decoration: line-through;
}
.green {
    background-color: green;
}
.light-blue {
    background-color: lightblue;
}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.slim.min.js"></script>
<script>
<?php echo file_get_contents(__DIR__ . '/js/utils.js'); ?>
<?php echo file_get_contents(__DIR__ . '/js/vdom.js'); ?>
<?php echo file_get_contents(__DIR__ . '/js/jdoh-vdom.js'); ?>
</script>
</head>
<body>
<div id="app"></div>
<script>

/** Todo app here */
const todoData = {title: 'My todo app', list: [{id: uuid(), name: 'wash', done: '', editting: false}, {id: uuid(), name: 'homework', done: '', editting: false}], newValue: '', msg: 'my message'};

const Title = makeReactTemplate({ 
    template: '<h2>{{title}}</h2>',
}, todoData)

const li = makeReactTemplate({ 
    template: `<li class="item {{done}}" ref="itemLi">
              <% if (editting === false) { %>
                {{name}}
              <% } else { %>
                <input type="text" value="{{name}}" onfocus="this.select()" jd-model="edittingItem" >
              <% } %>  
              <button @click="delete">-</button>
              <button @click="makeDone">{{done === "" ? "done" : "undone"}}</button>
              <button @click="startEdit">{{editting === false ? "edit" : "save"}}</button>
              <button ref="button" @click="changeBackground">change background</button>
              </li>`,
    dynamic: true,
    methods: {
        changeBackground() { 
            if ($(this.button).hasClass('light-blue')) {
                $(this.button).removeClass('light-blue');
                $(this.itemLi).removeClass('green');
            } else {
                $(this.button).addClass('light-blue');
                $(this.itemLi).addClass('green');
            }
        },
        startEdit(e, el, item) { 
            $(this.itemLi).addClass('light-blue');
            e.preventDefault();

            const list = todoData.list.get();
            for(let i=0; i < list.length; i++) {
                if (list[i].id === item.id) {
                    if (true === list[i].editting) {
                        list[i].editting = false;
                        // list[i].name = this.input.value; // in template li, you need ref="input"
                        if (undefined !== this.edittingItem) {
                            const value = this.edittingItem; 
                            list[i].name = value;
                        }
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
               <input type='text' onfocus="this.select()" jd-model="newItem" ref="input"><button @click="add">+</button>
               <br/><br/>
               <input type='text' value="{{msg}}" onfocus="this.select()" jd-model="msg" >
               <h1>Message: {{msg}}</h1>
               <p>{{msg}}</p>
               </div>`,
    methods:{
        add(e, el) { 
            e.preventDefault();
            const name = this.newItem;
            let res = typeof todoData.list.get === 'function' ? todoData.list.get() : [];
            const newItem = {name: name, id: uuid(), done: '', editting: false};
            res.push(newItem);
            console.log('add', newItem);
            this.input.value = '';
            todoData.set('list', res);
        }
    }           
}, todoData).mount();
</script>
</body>
</html>