
<template>
  <splitpanes 
    ref="inspectorSplitPanes"
    class="splitpane default-theme"
    :class="[classInbox ? 'splitpane-inbox' : 'splitpane', 'default-theme']"
    :dbl-click-splitter="false"
    @resize="sizeListener($event)">
    <pane style="opacity: 0">
      <div />
    </pane>
    <pane class="pane-task-preview" :min-size="paneMinSize" :size="size" max-size="99" style="background-color: white">
      <slot></slot>
    </pane>
  </splitpanes>
</template>

<script>
import { Splitpanes, Pane } from "splitpanes";
import "splitpanes/dist/splitpanes.css";

export default {
  components: { Splitpanes, Pane },
  props: {
    size: {
      default: 50,
    },
    classInbox: {
      default: false
    },
  },
  data() {
    return {
      paneMinSize: 0
    }
  },
  methods: {
    setPaneMinSize(splitpanesWidth, minPixelWidth) {
      this.paneMinSize = (minPixelWidth * 100) / splitpanesWidth;
    },
    sizeListener(event) {
      this.$root.$emit('pane-size', event[1].size);
    },
  },
  updated() {
    const resizeOb = new ResizeObserver((entries) => {
      const { width } = entries[0].contentRect;
      this.setPaneMinSize(width, 480);
    });
    if (this.$refs.inspectorSplitPanes) {
      resizeOb.observe(this.$refs.inspectorSplitPanes.container);
    }
  },
};
</script>

<style>
.splitpane {
  top: 0;
  min-height: 80vh;
  width: 99%;
  position: absolute;
}
.splitpane-inbox {
  top: 0;
  min-height: 80vh;
  width: 95%;
  /* width: 76%; */
  position: absolute;
}
.pane-task-preview {
  flex-grow: 1;
  overflow-y: auto;
}
</style>