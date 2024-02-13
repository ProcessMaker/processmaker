import Index from './components/Index.vue';
import Edit from './components/Edit.vue';
import { name } from 'mustache';

Vue.use(VueRouter);
const router = new VueRouter({
    mode: "history",
    base: "/tasks/rules",

    // See https://v3.router.vuejs.org/guide/
    routes: [
        {
            name: "index",
            path: "/",
            component: Index,
        },
        {
            name: "edit",
            path: "/:id",
            component: Edit,
        },
    ],
});

new Vue({
    router,
}).$mount("#inbox-rules");