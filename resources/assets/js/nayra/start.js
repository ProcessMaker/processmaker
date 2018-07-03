/**
 * Test POC of Nayra for Request form
 */
import RequestForm from './components/request-form.vue'

// Boot up our vue instance, creating a child Request Form, populated via blade for 
// process and event data
new Vue({
    el: '#start',
    components: {
        RequestForm
    }
})