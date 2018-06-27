<template>
  <div class="form-group">
    <label v-uni-for="name">{{label}}</label>
    <select
    v-uni-id="name"
    class="form-control"
    :class="{'is-invalid': error, classList}"
    :multiple='multiple'
    :disabled='disabled'
    :required='required'
    :name='name'
    :size='size'
    @change="updateValue">
        <option
        :selected="option.value == selected"
        :value="option.value"
        :key="index"
        v-for="(option, index) in options">
        {{option.content}}
        </option>
    </select>
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
    'selected',
    'value',
    'options',
    'helper',
    'disabled',
    'required',
    'size',
    'name',
    'controlClass',
    'multiple'
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
