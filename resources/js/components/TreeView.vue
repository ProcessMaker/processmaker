<template>
  <div>
    <iframe id="jsoncrackEmbed" src="https://jsoncrack.com/widget?json=639b65c5a82efc29a24b2de2" />
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
  height: 400px;
  border: none;
}
</style>
