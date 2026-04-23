<template>
  <div class="mt-2 text-left">
    <a
      v-if="!visible"
      href="#"
      class="link"
      @click.stop="visible = !visible"
    >Show debugging info</a>
    <div v-if="visible">
      <div
        ref="log"
        class="log card text-left"
      >
        <div
          v-for="(line, i) in logEntries"
          :key="i"
          class="entry"
          :class="{ warn: line.type === 'warn' }"
        >
          {{ line.message }}
        </div>
      </div>
      <div v-if="allowDownloadDebug">
        <a :href="'/import/download-debug?hash=' + $root.hash">Download Debug Data</a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    logEntries: {
      type: Array,
      default: () => [],
    },
    allowDownloadDebug: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      visible: false,
    };
  },
  watch: {
    logEntries() {
      if (!this.visible) {
        return;
      }
      this.$nextTick(() => this.$refs.log.scrollTop = this.$refs.log.scrollHeight);
    },
    allowDownloadDebug() {
      if (this.allowDownloadDebug) {
        this.visible = true;
      }
    },
  },
  methods: {
  },
};
</script>

<style type="text/css" scoped>
.log {
  max-height: 100px;
  overflow: scroll;
  margin: 10px;
  padding: 10px;
}
.entry {
  text-align: left;
  font-size: 12px;
  margin: 0;
  margin-bottom: 3px;
}
.link {
  font-size: 12px;
}
.warn {
  background-color: #FFAB00;
  font-weight: bold;
}
</style>
