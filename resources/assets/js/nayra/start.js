/**
 * Process start component
 */
import ProcessStart from './components/process-start.vue'
import ProcessCall from './components/process-call.vue'

// Boot up our vue instance
new Vue({
    el: '#start',
    components: {
        ProcessStart,
        ProcessCall
    }
})
