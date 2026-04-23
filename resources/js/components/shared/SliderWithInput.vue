<template>
  <b-form-group
    :label="label"
    :description="description"
    :invalid-feedback="error"
    :state="state"
  >
    <div class="d-flex align-items-center">
      <b-form-input
        v-model="configValue"
        type="number"
        class="w-25"
        :min="min"
        :max="max"
        :state="state"
      />
      <b-form-input
        v-model="configValue"
        type="range"
        :min="min"
        :max="max"
        :state="state"
        class="ml-3"
      />
    </div>
  </b-form-group>
</template>

<script>
export default {
  props: {
    label: String,
    description: String,
    type: String,
    error: String,
    value: [String, Number],
    min: {
      type: Number,
      default: 0,
    },
    max: {
      type: Number,
      default: 10,
    },
  },
  data() {
    return {
    };
  },
  computed: {
    configValue: {
      get() {
        return this.value || 0;
      },
      set(value) {
        this.$emit("input", value);
      },
    },
    state() {
      return this.error ? false : null;
    },
  },
};
</script>
