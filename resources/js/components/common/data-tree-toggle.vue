<template>
  <div class="flex-container">
    <i class="fas fa-code"></i>
    <div class="mb-2 custom-control custom-switch switch-padding">
      <input id="show_tree" type="checkbox" v-model="showTree"  class="custom-control-input" :checked="value" @input="emitSwitchValue">
      <label for="show_tree" class="custom-control-label"></label>
    </div>
    <i class="fas fa-project-diagram"></i>
  </div>
</template>

<script>
export default {
  props: {
    value: Boolean,
  },
  data() {
    return {
      showTree: this.value,
    };
  },
  methods: {
    emitSwitchValue(event) {
      this.$emit("input", event.target.checked);
    },
  },
};
</script>

<style>
.flex-container {
  display: flex;
  align-items: center;
}
.switch-padding {
    margin-bottom: 0rem !important;
    padding-left: 2.75rem;
}
</style>
