import Vue from 'vue'
import RolesListing from './components/RolesListing'

// Bootstrap our Designer application
new Vue({
  el: '#roles-listing',
  data: {
      filter: '' 
  },
  components: { RolesListing }
})