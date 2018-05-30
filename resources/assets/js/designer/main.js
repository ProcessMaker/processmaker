import Vue from 'vue'
import Designer from './Designer'

// Bootstrap our Designer application
new Vue({
  el: '#designer-container',
  components: { Designer },
  template: '<Designer/>'
})