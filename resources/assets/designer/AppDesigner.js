require("vue-resource");
window.Dispatcher = new Vue();

Vue.component("designer", require("./components/designer.vue"));
Vue.component("toolbar", require("./components/toolbar.vue"));
Vue.component("toptoolbar", require("./components/toptoolbar.vue"));
Vue.component("designer-objects-menu", require("./components/designer-objects-menu.vue"));

new Vue({
    el: "#appDesigner",

})

function autoResizeDiv() {
    document.getElementById('appDesigner').style.height = window.innerHeight - 60 + 'px';
}
autoResizeDiv()
