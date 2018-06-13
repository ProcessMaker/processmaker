import Vue from 'vue'
import Designer from './Designer'
import Editor from '@tinymce/tinymce-vue'
import draggable from 'vuedraggable'
import tinymce from 'tinymce/tinymce'
import 'tinymce/themes/modern/theme'


// Bootstrap our Designer application
new Vue({
  el: '#designer-container',
  components: { Designer,Editor, draggable },
  template: '<Designer/>'
})
