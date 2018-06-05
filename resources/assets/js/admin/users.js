import Vue from 'vue'
import UsersListing from './components/UsersListing'

// Bootstrap our Designer application
new Vue({
  el: '#users-listing',
  components: { UsersListing },
  template: '<UsersListing/>'
})