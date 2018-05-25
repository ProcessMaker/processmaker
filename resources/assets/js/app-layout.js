require('./bootstrap');
let Vue = window.Vue;

// Import our requests modal
import requestModal from './components/requests/modal'

// Setup our request modal and wire it to our button in the navbar
new Vue({
    el: '#navbar-request-button',
    components: {
        requestModal
    }
})