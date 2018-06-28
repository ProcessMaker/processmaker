<template>
  <div class="form-check">
    <label class="form-check-label" v-uni-for="name">
    <input
    v-uni-id="name"
    type="checkbox"
    class="form-check-input"
    :class="{'is-invalid': error, classList}"
    :name="name"
    :disabled="disabled"
    :required='required'
    :checked="value = checked"
    :value="value"
    :crop="crop"
    @change="updateValue">
    {{label}}</label>
    <div v-if="error" class="invalid-feedback">{{error}}</div>
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
    'crop',
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
