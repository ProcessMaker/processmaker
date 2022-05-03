import Vue from "vue";
import CustomSignalsListing from "./components/CustomSignalListing";
import SystemSignalsListing from "./components/SystemSignalsListing";
import ControllerSignalsListing from "./components/ControllerSignalsListing";

Vue.component('custom-signals-listing', CustomSignalsListing);
Vue.component('system-signals-listing', SystemSignalsListing);
Vue.component('controller-signals-listing', ControllerSignalsListing);
