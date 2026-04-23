<template>
  <b-modal
    v-model="showModal"
    dialog-class="top-20"
    :title="title"
    @hide="onClose"
  >
    <div
      class="my-3"
      :class="classMessage"
      v-html="message"
    />
    <template #modal-footer>
      <b-button
        class="m-0"
        @click="onClose"
      >
        Ok
      </b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  props: ["title", "message", "variant", "callback", "show"],
  data() {
    return {
      classMessage: "",
      classButtonCancel: "",
      classButtonConfirm: "",
      showModal: false,
    };
  },
  watch: {
    variant(value) {
      this.styles();
    },
    show(value) {
      this.showModal = value;
    },
  },
  mounted() {
    this.$emit("show");
    this.styles();
  },
  methods: {
    styles() {
      this.classMessage = "";
      this.classButtonCancel = "btn btn-outline-success btn-sm text-uppercase";
      this.classButtonConfirm = "btn btn-success btn-sm text-uppercase";
      if (this.variant) {
        this.classMessage += ` text-${this.variant}`;
        this.classButtonCancel += ` btn-outline-${this.variant}`;
        this.classButtonConfirm += ` btn-${this.variant}`;
      }
    },
    onClose() {
      if (this.callback) {
        this.callback();
      }
    },
  },
};
</script>

<style>
    .top-20 {
        top: 20%;
    }
</style>
