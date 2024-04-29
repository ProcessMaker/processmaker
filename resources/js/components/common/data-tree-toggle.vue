<template>
  <div class="flex-container">
    <i width="16" height="15">
      <img :src="codeViewIcon" alt="codeViewIcon">
    </i>
    <div class="mb-2 custom-control custom-switch switch-padding">
      <input
        id="show_tree"
        v-model="value"
        type="checkbox"
        :data-cy="value ? 'treeViewToggle-checked' : 'treeViewToggle'"
        class="custom-control-input"
        :checked="value"
        @input="emitSwitchValue"
      >
      <label for="show_tree" class="custom-control-label"></label>
    </div>
    <i width="16" height="15">
      <img :src="treeViewIcon" alt="treeViewIcon">
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
      treeViewIcon: require("../../../img/tree_view.svg"),
      codeViewIcon: require("../../../img/code.svg"),
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
