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
        <div class="modal-body justify-content-center">
          <h3 class="mt-3 font-weight-bold text-center">
            {{ $t("Welcome") }} {{ username }}
          </h3>
          <p class="text-center px-5">
            {{ $t("Check out your tasks and requests on your phone.") }}
          </p>
          <p class="text-center">
            <img
              class="image"
              src="/img/welcome_mobile.svg"
              alt="recent projects"
            >
          </p>
          <p class="text-center">
            {{ $t("To switch back to the previous interface, use \"Switch to Desktop View\" in the user menu.") }}
          </p>
          <button
            type="button"
            class="btn btn-primary w-100"
            @click="hideModal"
          >
            {{ $t("Got it!") }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

export default {
  props: ["username"],
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
