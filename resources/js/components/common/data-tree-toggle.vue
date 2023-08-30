<template>
  <div class="flex-container">
    <i class="fas fa-code"></i>
    <div class="mb-2 custom-control custom-switch switch-padding">
      <input id="show_tree" type="checkbox" v-model="showTree"  class="custom-control-input" :checked="value" @input="emitSwitchValue">
      <label for="show_tree" class="custom-control-label"></label>
    </div>
    <i width="16" height="15">
      <img :src="treeViewIcon">
    </i>
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
      treeViewIcon: require("../../../img/tree_view.svg"),
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
.tree-view-icon {
  font-weight: bold;
}
</style>
