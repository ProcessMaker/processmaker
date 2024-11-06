<template>
  <b-modal
    ref="bundleModal"
    centered
    @ok="onOk"
    :ok-title="modalOkButton"
    :cancel-title="'Cancel'"
  >
    <template #modal-header>
      <div class="delete-icon">
        <i class="fp-trash" />
      </div>
    </template>
    <p class="modal-body-text">{{ title }}</p>
    <p class="text-muted" v-html="message"></p>
  </b-modal>
</template>
<script>
import { ref } from 'vue';

export default {
  props: {
    title: {
      type: String,
      default: "",
    },
    message: {
      type: String,
      default: "",
    },
  },
  emits: ["delete"],
  setup(props, { emit }) {
    const bundleModal = ref(null);
    const modalOkButton = ref("Delete");

    const show = () => {
      if (bundleModal.value) {
        bundleModal.value.show();
      }
    };

    const hide = () => {
      if (bundleModal.value) {
        bundleModal.value.hide();
      }
    };

    const onOk = () => {
      emit("delete", true);
    };

    return {
      bundleModal,
      show,
      hide,
      onOk,
      modalOkButton,
    };
  },
};

</script>
<style lang="scss" scoped>
@import "styles/components/modal";
::v-deep .modal-header .delete-icon {
  width: 48px;
  height: 48px;
  background-color: #FEE6E5;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  color: #EC5962;
  font-size: 26px;
}
::v-deep .modal-body-text {
  font-size: 16px;
  font-weight: 500;
}
::v-deep .modal-footer {
  background-color: #FBFBFC;
  border-top: 1px solid #E9ECF1;
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
}
::v-deep .modal-footer .btn-primary {
  border: none;
  background-color: #EC5962;
  color: #FFFFFF;
}
::v-deep .modal-footer .btn-primary:hover {
  background-color: #c74a51;
}
::v-deep .modal-footer .btn-secondary {
  border: 1px solid #D7DDE5;
  background-color: #FFFFFF;
  color: #20242A;
}
</style>
