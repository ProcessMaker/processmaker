require('./bootstrap');
let Vue = window.Vue;

new Vue({
    el: '#sidebarMenu',
    data: {
        expanded: false,
        icon: '/img/processmaker-icon-white-sm.png',
        logo: '/img/processmaker-logo-white-sm.png'
    },
    methods: {
        toggleVisibility() {
            this.expanded = !this.expanded;
        }
    }
})

$("#menu-toggle").click(function (e) {
    e.preventDefault();
    $("#wrapper").toggleClass("toggled");
});

// Import our requests modal
import requestModal from './components/requests/modal'

// Setup our request modal and wire it to our button in the navbar
new Vue({
    el: '#navbar-request-button',
    components: {
        requestModal
    }
})
