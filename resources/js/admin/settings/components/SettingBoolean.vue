<template>
  <span>
    <b-form-checkbox :key="key" @input="emitSaved(input)" v-model="input" switch></b-form-checkbox>
  </span>
</template>

<script>
import settingMixin from "../mixins/setting";

export default {
  mixins: [settingMixin],
  props: ['value', 'setting'],
  data() {
    return {
      input: this.value,
      key: null,
    };
  },
  watch: {
    value: {
      handler: function(value) {
        this.regenerateKey();
        this.input = value;
      },
    }
  },
  methods: {
    regenerateKey() {
      this.key = Math.random().toString(36).substring(7);
    }
  },
  mounted() {
    this.regenerateKey();
    this.input = this.value;
  }
};
</script>
