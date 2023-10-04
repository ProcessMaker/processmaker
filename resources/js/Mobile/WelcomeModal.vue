<template>
  <div
    id="welcomeModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
  >
    <div
      class="modal-dialog modal-dialog-centered"
      role="document"
    >
      <div class="modal-content">
        <div class="modal-body">
          <p>Modal body text goes here.</p>
          <img
            class="image"
            src="/img/welcome_mobile.svg"
            alt="recent projects"
          >
          <button
            type="button"
            class="btn btn-primary"
            @click="hideModal"
          >
            {{ $t('Got it') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

export default {
  data() {
    return {};
  },
  methods: {
    hideModal() {
      this.$cookies.set("firstMounted", false);
      $("#welcomeModal").modal("hide");
    },
  },
};
</script>

<style scoped>
a,
.dateInfo {
  color: #57646F;
}
.titleInfo {
  color: #1572C2;
  font-size: 16px;
  font-style: normal;
  font-weight: 600;
  line-height: normal;
}
.badge-custom {
  color: #44494E;
  background-color: #FFF5DB;
}
.align-left {
  text-align: end;
}
</style>
