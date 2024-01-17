<template>
  <div
    v-cloak
    class="nav-item"
  >
    <button
      id="navbar-request-button-mobile"
      type="button"
      class="btn btn-success border border-white"
      aria-haspopup="dialog"
      :aria-label="$t('New Case')"
      @click="showRequestModal"
    >
      <i class="fas fa-plus" />
    </button>
    <new-request-modal
      ref="requestModal"
      size="md"
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
    showRequestModal() {
      // Perform initial load of requests from backend
      this.$refs.requestModal.showModal();
    },
  },
};
</script>
