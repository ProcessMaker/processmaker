import Vue from "vue";
import "bootstrap-vue/dist/bootstrap-vue.css";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import VuePassword from "vue-password";
import AvatarImage from "../../../components/AvatarImage.vue";

Vue.use(BootstrapVue);
Vue.use(BootstrapVueIcons);

Vue.component("AvatarImage", AvatarImage);
Vue.component("VuePassword", VuePassword);
