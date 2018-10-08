import Vue from 'vue'
import VariablesListing from './components/VariablesListing'

// Bootstrap our Variables listing
new Vue({
    el: '#variablesIndex',
    data: {
        filter: '',
    },
    components: {
        VariablesListing
    }
});
