<template>
  <b-modal
    :id="id"
    ref="pmModal"
    footer-class="pm-modal-footer"
    cancel-variant="outline-secondary"
    :cancel-title="$t('Cancel')"
    ok-variant="secondary"
    :ok-title="okTitle ? okTitle : $t('Save')"
    :ok-disabled="okDisabled"
    :ok-only="okOnly"
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
   <template #modal-title>
      <h5>{{title}}</h5>
      <small v-if="subtitle" class="text-muted subtitle">{{subtitle}}</small>
    </template>
    <slot></slot>
  </b-modal>
</template>

<script>
  export default {
    props: ["id", "title", "subtitle", "ok-disabled", 'ok-title', 'ok-only'],
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

  .subtitle {
    font-size: 70%;
  }

</style>
