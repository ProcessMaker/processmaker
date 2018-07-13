import Vue from 'vue'
import RequestsListing from './components/RequestsListing'

// Bootstrap our Designer application
new Vue({
  data: {
    filter: ''
  },
  el: '#requests-listing',
  components: {RequestsListing}
})