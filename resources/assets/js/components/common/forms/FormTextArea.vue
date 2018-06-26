<template>
  <div class="form-group">
  <label v-uni-for="label">{{label}}</label>
    <textarea v-uni-id="label" :placeholder="placeholder" class="form-control" :class="{'is-invalid': error, classList}" @input="updateValue"></textarea>
    <div v-if="error" class="invalid-feedback">{{error}}</div>
  </div>
</template>


<script>
import { createUniqIdsMixin } from 'vue-uniq-ids'

// Create the mixin
const uniqIdsMixin = createUniqIdsMixin()

export default {
  mixins: [uniqIdsMixin],
  props: [
    'label',
    'error',
    'placeholder',
    'value',
    'controlClass'
  ],
  computed:{
    classList(){
      let classList = {}
      if(this.controlClass){
        classList[this.controlClass] = true
      }
      return classList
    }
  },
  data() {
    return {
      content: '',
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
