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

window.ProcessMaker.nodeTypes = [];
window.ProcessMaker.nodeTypes.get = function (id) {
    return this.find(node => node.id === id);
}

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
            alerts: [],
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
window.ProcessMaker.alert = function (msg, variant, showValue = 3) {
    if (showValue === 0) {
        // Just show it indefinitely, no countdown
        showValue = true;
    }
    ProcessMaker.navbar.alerts.push({
        alertText: msg,
        alertShow: showValue,
        alertVariant: String(variant)
    })
};

// Set out own specific confirm modal.
window.ProcessMaker.confirmModal = function (title, message, variant, callback) {
    ProcessMaker.navbar.confirmTitle = title || __("Confirm");
    ProcessMaker.navbar.confirmMessage = message || __("Are you sure you want to delete?");
    ProcessMaker.navbar.confirmVariant = variant;
    ProcessMaker.navbar.confirmCallback = callback;
    ProcessMaker.navbar.confirmShow = true;
};

window.ProcessMaker.apiClient.interceptors.response.use((response) => {
    // TODO: this could be used to show a default "created/upated/deleted resource" alert
    // response.config.method (PUT, POST, DELETE)
    // response.config.url (extract resource name)
    return response;
}, (error) => {
    if (error.response.status == 422 && error.config.context) {
        // This is a standard laravel validation error
        error.config.context.errors = error.response.data.errors;
        ProcessMaker.alert(
            'An error occurred. Check the form for errors in red text.',
            'danger'
        );
        error.config._defaultErrorShown = true;
    }
    return Promise.reject(error);
});

// Display any uncaught promise rejections from axios in the Process Maker alert box
window.addEventListener('unhandledrejection', function (event) {
    let error = event.reason;
    if (error.config._defaultErrorShown) {
        // Already handeled
        event.preventDefault(); // stops the unhandled rejection error
    } else if (error.response.data && error.response.data.message) {
        window.ProcessMaker.alert(error.response.data.message, "danger");
    } else if (error.message) {
        window.ProcessMaker.alert(error.message, "danger");
    }
});

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