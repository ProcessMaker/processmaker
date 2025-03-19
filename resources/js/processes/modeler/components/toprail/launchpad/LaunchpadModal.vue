<template>
  <b-modal
    ref="launchpad-modal"
    :title="$t('You have not publish your process')"
    @hide="closeModal"
  >
    <p>
      {{ $t('You have not published your process. Please publish your process to use the Launchpad.') }}
    </p>
    <template #modal-footer>
      <b-button
        variant="primary"
        size="sm"
        class="float-right"
        @click="closeModal"
      >
        {{ $t('Cancel') }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
export default {
  props: {
    show: {
      type: Boolean,
      required: true,
    },
  },
  watch: {
    show(value) {
      if (value) {
        this.$refs['launchpad-modal'].show();
      }
    },
  },
  methods: {
    closeModal() {
      this.$emit('closeModal');
      this.$refs['launchpad-modal'].hide();
    },
  },
  showModal() {
    this.$refs['launchpad-modal'].show();
  },
};
</script>
