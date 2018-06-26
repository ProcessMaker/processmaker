<template>
<div class="form-group">
  <label>{{label}}</label>
  <div class="form-check" v-for="(option) in options">
    <label class="form-check-label" v-uni-for="option.label">
    <input class="form-check-input" :class="{'is-invalid': error, classList}" type="radio" :name="name" v-uni-id="option.label" :value="option.value" @change="updateValue" :selected="options.value == this.value">
    {{option.label}}</label>
  </div>
</div>
</template>

<script>
import { createUniqIdsMixin } from 'vue-uniq-ids'

// Create the mixin
const uniqIdsMixin = createUniqIdsMixin()

export default {
  mixins: [uniqIdsMixin],
  props: [
    'error',
    'selected',
    'value',
    'options',
    'label',
    'name',
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
