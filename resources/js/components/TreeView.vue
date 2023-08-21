<template>
  <div>
    <iframe id="jsoncrackEmbed" ref="jsonCrackEmbed" src="/json-browser/widget.html"/>
  </div>
</template>

<script>
export default {
  props: {
    value: {
      type: String,
    },
  },
  data() {
    return {
      jsonData: this.value,
    };
  },
  watch: {
    value: {
      handler(newVal) {
        this.jsonData = newVal;
      },
      deep: true,
    },
  },
  mounted() {
    const jsonCrackEmbed = this.$refs.jsonCrackEmbed;

    const json = this.jsonData;
    const options = {
      theme: "light",
      direction: "RIGHT",
    };

    this.handleMessage = () => {
      const iframeOrigin = jsonCrackEmbed.src;

      jsonCrackEmbed.contentWindow.postMessage(
        {
          json,
          options,
        },
        iframeOrigin,
      );
    };

    window?.addEventListener("message", this.handleMessage);
  },
  beforeDestroy() {
    window.removeEventListener("message", this.handleMessage);
  },
  methods: {
    handleInput() {
      this.$emit("input", this.jsonData);
    },
  },
};
</script>

<style>
#jsoncrackEmbed {
  flex: 1;
  order: 2;
  width: 100%;
  height: 700px;
  border: none;
}
</style>
