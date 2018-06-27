<template>
<div class="form-group">
  <label>{{label}}</label>
  <div class="form-check" v-for="(option) in options">
    <label class="form-check-label" v-uni-for="name">
    <input class="form-check-input"
    :class="{'is-invalid': error, classList}"
    type="radio"
    :name="name"
    :disabled="disabled"
    :required='required'
    v-uni-id="name"
    :value="option.value"
    @change="updateValue"
    :checked="options.value = checked">
    {{option.content}}</label>
  </div>
  <small v-if="helper" class="form-text text-muted">{{helper}}</small>
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
    'checked',
    'value',
    'options',
    'disabled',
    'required',
    'label',
    'name',
    'helper',
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
