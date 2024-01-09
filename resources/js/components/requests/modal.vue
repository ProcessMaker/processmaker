<template>
  <div
    v-cloak
    class="nav-item p-2"
  >
    <button
      id="navbar-request-button"
      type="button"
      class="btn btn-success btn-sm"
      :aria-label="$t('New Case')"
      aria-haspopup="dialog"
      @click="showRequestModal"
    >
      <i class="fas fa-plus" />
      {{ $t('Case') }}
    </button>
    <new-request-modal
      ref="requestModal"
      size="lg"
      :permission="permission"
      :url="url"
    />
  </div>
</template>

<script>
import newRequestModal from "./requestModal.vue";

export default {
  components: {
    "new-request-modal": newRequestModal,
  },
  props: {
    permission: Array,
    url: "",
  },
  data() {
    return {
    };
  },
  methods: {
    hasEmptyStartEvents(process) {
      return !!process.events.find((event) => !event.eventDefinitions || event.eventDefinitions.length === 0);
    },
    showRequestModal() {
      this.$refs.requestModal.showModal();
    },
  },
};
</script>

<style lang="scss" scoped>
    .icon-container {
        display:inline-block;
        width: 5em;
        margin-bottom: 1em;

        i {
            color: #b7bfc5;
            font-size: 5em;
        }

        svg {
            fill: #b7bfc5;
        }
    }
</style>
