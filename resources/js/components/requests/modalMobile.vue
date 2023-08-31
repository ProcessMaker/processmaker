<template>
  <li
    v-cloak
    class="nav-item"
  >
    <button
      id="navbar-request-button-mobile"
      type="button"
      class="btn btn-success border border-white"
      aria-haspopup="dialog"
      :aria-label="$t('New Request')"
      @click="showRequestModal"
    >
      <i class="fas fa-plus" />
    </button>
    <b-modal
      id="requests-modal-mobile"
      ref="requestModalMobileAdd"
      class="requests-modal-mobile modal-dialog-scrollable"
      :title="$t('New Request')"
      header-close-content="&times;"
      hide-footer
    >
      <p> contenido </p>
    </b-modal>
  </li>
</template>

<script>

export default {
  props: {},
  data() {
    return {
    };
  },
  methods: {
    showRequestModal() {
      // Perform initial load of requests from backend
      this.$refs.requestModalMobileAdd.show();
    },
  },
};
</script>
