<template>
  <div class="form-group">
    <label v-uni-for="name">{{label}}</label>
    <input
    v-uni-id="name"
    :required='required'
    :placeholder="placeholder"
    type="text"
    :type="type"
    :minlength="minlength"
    :maxlength="maxlength"
    :name="name"
    :disabled="disabled"
    class="form-control"
    :class="{'is-invalid': error, classList}"
    @input="updateValue">
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
    'label',
    'error',
    'helper',
    'type',
    'name',
    'minlength',
    'maxlength',
    'required',
    'disabled',
    'placeholder',
    'value',
    'controlClass',
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
