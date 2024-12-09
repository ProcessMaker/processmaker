import Router from "vue-router";

window.VueRouter = Router;

// if (!document.head.querySelector("meta[name=\"is-horizon\"]")) {
window.Vue.use(Router);
// }

window.ProcessMaker.Router = new Router({
  mode: "history",
});
