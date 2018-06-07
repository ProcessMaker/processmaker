import Vue from 'vue'
import TasksListing from './components/TasksListing'

// Bootstrap our Designer application
new Vue({
  el: '#tasks-listing',
  data: {
      filter: '' 
  },
  components: { TasksListing }
});