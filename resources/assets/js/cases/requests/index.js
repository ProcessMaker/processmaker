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
        window.location.href = '/cases/requests?delay=overdue';
    },
      loadRequestsAtRisk() {
      window.location.href = '/cases/requests?delay=at_risk';
    },
      loadRequestsOnTime() {
      window.location.href = '/cases/requests?delay=on_time';
    },
  }
})