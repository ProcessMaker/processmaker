<template>
  <b-modal
    :id="id"
    ref="pmModal"
    :title="title"
    footer-class="pm-modal-footer"
    cancel-variant="outline-secondary"
    :cancel-title="$t('Cancel')"
    ok-variant="secondary"
    :ok-title="$t('Save')"
    :ok-disabled="okDisabled"
    no-close-on-backdrop
    centered
    @cancel="onEvent('cancel', $event)"
    @change="onEvent('change', $event)"
    @close="onEvent('close', $event)"
    @hidden="onEvent('hidden', $event)"
    @hide="onEvent('hide', $event)"
    @ok="onEvent('ok', $event)"
    @show="onEvent('show', $event)"
    @shown="onEvent('shown', $event)"
  >
    <slot></slot>
  </b-modal>
</template>

<script>
  export default {
    props: ["id", "title", "ok-disabled"],
    methods: {
      onEvent(name, event) {
        this.$emit(name, event);
      },
      show() {
        this.$refs.pmModal.show();
      },
      hide() {
        this.$refs.pmModal.hide();
      }
    }
  };
</script>

<style>
  .pm-modal-footer .btn {
    margin: 0;
  }
</style>
