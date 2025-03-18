<template>
  <button
    class="pm-agent-button d-flex flex-column align-items-center"
    :disabled="disabled"
    :title="$t('PM Agents')"
    @click="handleClick"
  >
    <img
      src="/img/pm-agents.svg"
      :alt="$t('PM Agents')"
    >
    <span class="pt-1">{{ $t("PM Agents") }}</span>
  </button>
</template>

<script>
export default {
  props: {
    disabled: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    handleClick() {
      window.location.href = "/designer/pm-agents";
    },
  },
};
</script>

<style scoped>
.pm-agent-button {
  background: #ffffff;
  border-radius: 8px;
  border: 1px solid #D3DBE5;
  padding: 1rem 1.25rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pm-agent-button:hover:not(:disabled) {
  background-color: #f8f9fa;
  border-color: #c0c9d6;
}

.pm-agent-button:active:not(:disabled) {
  background-color: #e9ecef;
}

.pm-agent-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.pm-agent-button > img {
  height: 1.3rem;
}
</style>
