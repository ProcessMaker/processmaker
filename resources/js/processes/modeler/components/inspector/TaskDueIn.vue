<template>
  <div class="form-group">
    <label>{{ $t("Due In") }}</label>
    <b-form-checkbox
      v-model="isCheckDueIn"
      name="checkDueInVariable"
    >
      {{ $t("Use request Variable") }}
    </b-form-checkbox>
    <template v-if="!isCheckDueIn">
      <input
        class="form-control"
        :aria-label="$t('Enter the hours until this Task is overdue')"
        type="number"
        :placeholder="$t('72 hours')"
        :value="dueInGetter"
        min="0"
        @input="dueInSetter"
        @keydown="dueInValidate"
      >
      <small class="form-text text-muted">{{ $t("Enter the hours until this Task is overdue") }}</small>
    </template>
    <template v-else>
      <input
        class="form-control"
        type="text"
        :placeholder="$t('Type')"
        :value="dueInVariableGetter"
        @input="dueInVariableSetter"
        @keydown="dueInValidate"
      >
      <small class="form-text text-muted">{{ $t("This field supports mustache syntax to send requests variables.") }}</small>
    </template>
  </div>
</template>

<script>
export default {
  props: ["value", "label", "helper", "property"],
  computed: {
    dueInGetter() {
      return _.get(this.node, "dueIn");
    },
    node() {
      return this.$root.$children[0].$refs.modeler.highlightedNode.definition;
    },
    isCheckDueIn: {
      get() {
        return _.get(this.node, "isDueInVariable");
      },
      set(val) {
        this.$set(this.node, "isDueInVariable", val);
      },
    },
    dueInVariableGetter() {
      return _.get(this.node, "dueInVariable");
    },
  },
  methods: {
    dueInValidate(event) {
      if (event.key === "-") {
        event.preventDefault();
      }
    },
    /**
       * Update due in property
       */
    dueInSetter(event) {
      const validValue = Math.abs(event.target.value * 1) || "";
      if (!validValue) {
        this.$delete(this.node, "dueIn");
      } else {
        this.$set(this.node, "dueIn", validValue);
      }
      this.$emit("input", this.value);
      String(validValue) !== event.target.value
        ? (event.target.value = validValue)
        : null;
    },
    /**
     * Updte due in with variable
     */
    dueInVariableSetter(event) {
      this.$set(this.node, "dueInVariable", event.target.value);
    },
  },
};
</script>

<style lang="scss" scoped>
</style>
