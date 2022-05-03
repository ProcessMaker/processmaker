import Vue from "vue";
import CustomSignalsListing from "./components/CustomSignalListing";
import SystemSignalsListing from "./components/SystemSignalsListing";
import CollectionSignalsListing from "./components/CollectionSignalsListing";

Vue.component('custom-signals-listing', CustomSignalsListing);
Vue.component('system-signals-listing', SystemSignalsListing);
Vue.component('collection-signals-listing', CollectionSignalsListing);
