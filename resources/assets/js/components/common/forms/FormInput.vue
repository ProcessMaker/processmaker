<template>
  <div class="form-group">
    <label v-uni-for="label">{{label}}</label>
    <input v-uni-id="label" :placeholder="placeholder" class="form-control" :class="{'is-invalid': error}" @input="updateValue">
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
  ],
  data() {
    return {
      content: ''
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
