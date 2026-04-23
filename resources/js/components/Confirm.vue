<template>
  <b-modal
    v-model="showModal"
    dialog-class="top-20"
    :title="title"
    :size="size ? size : 'md'"
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
        variant="outline-secondary"
        :data-test="dataTestClose"
        @click="onDeny"
      >
        Cancel
      </b-button>
      <b-button
        class="m-0"
        :data-test="dataTestOk"
        @click="onConfirm"
      >
        Confirm
      </b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  props: ["title", "message", "variant", "callback", "show", "size", "dataTestClose", "dataTestOk"],
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
      this.$emit("close");
    },
    onConfirm() {
      this.$emit("confirm", true);
      if (this.callback) {
        this.callback();
      }
      this.onClose();
    },
    onDeny() {
      this.$emit("confirm", false);
      this.onClose();
    },
  },
};
</script>

<style>
    .top-20 {
        top: 20%;
    }
</style>
