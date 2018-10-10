import Vue from 'vue'
import ScriptListing from './components/ScriptListing'

new Vue({
    el: '#scriptIndex',
    data: {
        filter: ''
    },
    components: {
        ScriptListing
    }
});
