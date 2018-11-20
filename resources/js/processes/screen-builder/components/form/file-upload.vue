<template>
  <div class="form-group">
    <label v-uni-for="name">{{label}}</label>
    <uploader :options="options">
      <uploader-unsupport></uploader-unsupport>
      <uploader-drop>
        <p>Drop files here to upload or</p>
        <uploader-btn>select files</uploader-btn>
      </uploader-drop>
      <uploader-list></uploader-list>

    </uploader>
    <div class="invalid-feedback" v-if="error">{{error}}</div>
    <small v-if="helper" class="form-text text-muted">{{helper}}</small>
  </div>
</template>


<script>
import { createUniqIdsMixin } from 'vue-uniq-ids'
import uploader from 'vue-simple-uploader'


// Create the mixin
const uniqIdsMixin = createUniqIdsMixin()

export default {
    components: uploader,
  mixins: [uniqIdsMixin],
  props: [
    'label',
    'error',
    'helper',
    'name',
    'value',
    'controlClass',
  ],
  computed:{
    classList(){
      let classList = {
        'is-invalid': (this.validator && this.validator.errorCount) || this.error, 
      }
      if(this.controlClass) {
        classList[this.controlClass] = true
      }
      return classList
    }
  },
  data() {
    return {
      options: {
        // @todo Replace 123 with the current request id, maybe populated via meta tag?
        target: '/api/1.0/requests/123/files',
        query: {
          chunk: true
        },
        // Setup our headers to deal with API calls
        headers: {
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": window.ProcessMaker.apiClient.defaults.headers.common['X-CSRF-TOKEN']
        },
        singleFile: true
      },
      content: '',
      validator: null
    }
  },
  methods: {
   updateValue(e) {
      this.content = e.target.value;
      this.$emit('input', this.content)
    }
  }
}
</script>

<style lang="scss" scoped>
</style>