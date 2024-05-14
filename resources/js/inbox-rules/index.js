import Index from './components/Index.vue';
import Edit from './components/Edit.vue';
import TasksList from "../tasks/components/TasksList.vue";
Vue.component("TasksList", TasksList);

Vue.use(VueRouter);
const router = new VueRouter({
  mode: "history",
  base: "/tasks/rules",
  //See https://v3.router.vuejs.org/guide/
  routes: [
    {
      name: "index",
      path: "/",
      component: Index
    },
    {
      name: "new",
      path: "/new",
      component: Edit,
      props(route) {
        return {
          ruleId: null,
          newSavedSearchId: parseInt(route.query.saved_search_id) || null,
          newTaskId: parseInt(route.query.task_id) || null,
          elementId: route.query.element_id || null,
          processId: parseInt(route.query.process_id) || null
        };
      }
    },
    {
      name: "edit",
      path: "/:id",
      component: Edit,
      props(route) {
        return {
          ruleId: parseInt(route.params.id)
        };
      }
    },
  ]
});

new Vue({
  router
}).$mount("#inbox-rules");