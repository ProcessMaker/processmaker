<template>
  <span>
    <b-form-checkbox
      :key="key"
      v-model="input"
      switch
      @input="emitSaved(input)"
    />
  </span>
</template>

<script>
import settingMixin from "../mixins/setting";

export default {
  mixins: [settingMixin],
  props: ["value", "setting"],
  data() {
    return {
      input: this.value,
      key: null,
    };
  },
  watch: {
    value: {
      handler(value) {
        this.regenerateKey();
        this.input = value;
      },
    },
  },
  mounted() {
    this.regenerateKey();
    this.input = this.value;
  },
  methods: {
    regenerateKey() {
      this.key = Math.random().toString(36).substring(7);
    },
  },
};
</script>
