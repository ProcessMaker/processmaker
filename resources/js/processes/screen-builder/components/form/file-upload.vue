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
      content: '',
      validator: null,
      requestID: document.head.querySelector("meta[name=\"request-id\"]").content,
      options: {
        target: '/api/1.0/requests/' + this.requestID + '/files',
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