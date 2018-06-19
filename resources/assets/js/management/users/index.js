import Vue from 'vue'
import UsersListing from './components/UsersListing'

// Bootstrap our Designer application
new Vue({
  data: {
    filter: ''
  },
  el: '#users-listing',
  components: { UsersListing }
})