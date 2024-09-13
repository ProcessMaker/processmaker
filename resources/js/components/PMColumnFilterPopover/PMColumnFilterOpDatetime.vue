<template>
  <PMDatetimePicker v-model="input"
                    :placeholder="format"
                    :format="format"
                    :withTime="true"
                    :size="'sm'"
                    :timeZone="timeZone"
                    :guestTimeZone="timeZone">
  </PMDatetimePicker>
</template>

<script>
  import PMDatetimePicker from "../../components/PMDatetimePicker.vue";
  export default {
    components: {
      PMDatetimePicker
    },
    props: {
      value: {
        type: null,
        default: ""
      }
    },
    data() {
      return {
        input: this.value,
        format: window.ProcessMaker.user.datetime_format,
        timeZone: window.ProcessMaker.user.timezone
      };
    },
    watch: {
      value: {
        handler(newValue) {
          this.input = newValue;
        },
        immediate: true
      },
      input() {
        this.$emit("input", this.input);
      }
    }
  };
</script>