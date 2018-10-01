import Vue from 'vue'
import RequestsListing from './components/RequestsListing'

// Bootstrap our Designer application
new Vue({
  data: {
    filter: ''
  },
  el: '#requests-listing',
  components: {RequestsListing},
  methods: {
    loadRequestsOverdue() {
        window.location.href = '/requests?delay=overdue';
    },
      loadRequestsAtRisk() {
      window.location.href = '/requests?delay=at_risk';
    },
      loadRequestsOnTime() {
      window.location.href = '/requests?delay=on_time';
    },
  }
})
