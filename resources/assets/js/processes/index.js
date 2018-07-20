import Vue from 'vue'
import ProcessesListing from './components/ProcessesListing'

new Vue({
    el: '#processes-listing',
    data: {
        filter: ''
    },
    components: {ProcessesListing}
});
