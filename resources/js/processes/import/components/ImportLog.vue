<template>
  <div class="mt-2 text-left">
    <a href="#" class="link" v-if="!visible" @click.stop="visible = !visible">Show debugging info</a>
    <div v-if="visible">
      <div ref="log" class="log card text-left">
        <div class="entry" :class="{ warn: line.type === 'warn' }" v-for="(line, i) in logEntries" :key="i">{{ line.message }}</div>
      </div>
      <div v-if="allowDownloadDebug">
        <a :href="'/import/download-debug?hash=' + $root.hash">Download Debug Data</a>
      </div>
    </div>
  </div>
</template>


<script>
export default {
  data() {
    return {
      visible: false,
    };
  },
  props: {
    logEntries: {
      type: Array,
      default: () => []
    },
    allowDownloadDebug: {
      type: Boolean,
      default: false,
    }
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
    }
  },
  methods: {
  }
}
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