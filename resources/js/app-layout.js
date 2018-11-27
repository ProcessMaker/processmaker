require('./bootstrap');
let Vue = window.Vue;

import requestModal from "./components/requests/modal";
import notifications from "./components/requests/notifications";
import {
    Navbar
} from "bootstrap-vue/es/components";
import ConfirmationModal from "./components/Confirm";
import NavbarProfile from "./components/NavbarProfile";
import Multiselect from 'vue-multiselect/src/Multiselect';

/******
 * Global adjustment parameters for moment.js.
 */
import moment from "moment"
import moment_timezone from "moment-timezone";
if (window.ProcessMaker && window.ProcessMaker.user) {
    moment.tz.setDefault(window.ProcessMaker.user.timezone);
    moment.defaultFormat = window.ProcessMaker.user.datetime_format;
    moment.defaultFormatUtc = window.ProcessMaker.user.datetime_format;
}
Vue.prototype.moment = moment;
//initializing global instance of a moment object
window.moment = moment;
/********/

Vue.component('multiselect', Multiselect);

//Event bus ProcessMaker
window.ProcessMaker.events = new Vue();

// Assign our navbar component to our global ProcessMaker object
window.ProcessMaker.navbar = new Vue({
    el: "#navbar",
    components: {
        Navbar,
        requestModal,
        notifications,
        ConfirmationModal,
        NavbarProfile
    },
    data() {
        return {
            messages: ProcessMaker.notifications,
            alertShow: false,
            alertText: "",
            alertVariant: "",
            confirmTitle: "",
            confirmMessage: "",
            confirmVariant: "",
            confirmCallback: "",
            confirmShow: false
        };
    },
    mounted() {
        Vue.nextTick() // This is needed to override the default alert method.
            .then(() => {
                if (document.querySelector("meta[name='alert']")) {
                    ProcessMaker.alert(
                        document.querySelector("meta[name='alertMessage']").getAttribute("content"),
                        document.querySelector("meta[name='alertVariant']").getAttribute("content")
                    );
                }
            });
    }
});

// Set our own specific alert function at the ProcessMaker global object that could
// potentially be overwritten by some custom theme support
window.ProcessMaker.alert = function (msg, variant) {
    ProcessMaker.navbar.alertText = msg;
    ProcessMaker.navbar.alertShow = true;
    ProcessMaker.navbar.alertVariant = String(variant);
};

// Set out own specific confirm modal.
window.ProcessMaker.confirmModal = function (title, message, variant, callback) {
    ProcessMaker.navbar.confirmTitle = title || "Confirm";
    ProcessMaker.navbar.confirmMessage = message || "Are you sure to delete?";
    ProcessMaker.navbar.confirmVariant = variant;
    ProcessMaker.navbar.confirmCallback = callback;
    ProcessMaker.navbar.confirmShow = true;
};

// Setup our api client interceptor to handle errors and reflect the error
// in our skin.
window.ProcessMaker.apiClient.interceptors.response.use(
    response =>
    // No need to handle success responses
    response, (error) => {
        let elem = document.getElementById("content-inner");
        if (error.response.status != 422 && error.response.status != 404 && elem !== null) {
            // Replace our content div with our error div
            // Remove our #content-inner
            elem.parentNode.removeChild(elem);
            // Now show our #api-error div
            elem = document.getElementById("api-error");
            elem.setAttribute("style", "display: block");
        }
        if (error.response.data && error.response.data.message) {
            window.ProcessMaker.alert(error.response.data.message, "danger");
        }
        return Promise.reject(error);
    }
);

new Vue({
    el: "#sidebar",
    data() {
        return {
            expanded: false
        };
    }
});

// Use this method to trigger the sidebar menu to open and closed
$("#menu-toggle").click((e) => {
    e.preventDefault();

    if (document.getElementById("sidebar-inner").classList.contains("closed")) {
        document.getElementById("sidebar").classList.remove("closed");
        document.getElementById("sidebar-inner").classList.remove("closed");
    } else {
        document.getElementById("sidebar").classList.add("closed");
        document.getElementById("sidebar-inner").classList.add("closed");
    }
});