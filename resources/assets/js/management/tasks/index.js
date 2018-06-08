import Vue from 'vue'
import TasksListing from './components/TasksListing'

new Vue({
  el: '#tasks-listing',
  data: {
      filter: '' 
  },
  components: { TasksListing }
});
