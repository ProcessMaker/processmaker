import Vue from 'vue'
import Designer from './Designer'
import tinymce from 'tinymce/tinymce'
import Editor from '@tinymce/tinymce-vue'


// Bootstrap our Designer application
new Vue({
  el: '#designer-container',
  components: { Designer },
  template: '<Designer/>'
})
