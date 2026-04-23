<template>
  <PMDatetimePicker
    v-model="input"
    :placeholder="format"
    :format="format"
    :with-time="true"
    :size="'sm'"
    :time-zone="timeZone"
    :guest-time-zone="timeZone"
  />
</template>

<script>
import PMDatetimePicker from "../PMDatetimePicker.vue";

export default {
  components: {
    PMDatetimePicker,
  },
  props: {
    value: {
      type: null,
      default: "",
    },
  },
  data() {
    return {
      input: this.value,
      format: window.ProcessMaker.user.datetime_format,
      timeZone: window.ProcessMaker.user.timezone,
    };
  },
  watch: {
    value: {
      handler(newValue) {
        this.input = newValue;
      },
      immediate: true,
    },
    input() {
      this.$emit("input", this.input);
    },
  },
};
</script>
