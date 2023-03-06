<template>
  <b-modal
    :id="id"
    ref="pmModal"
    :title="title"
    footer-class="pm-modal-footer"
    cancel-variant="outline-secondary"
    :cancel-title="$t('Cancel')"
    ok-variant="primary"
    :ok-title="okTitleWithDefault"
    :ok-disabled="okDisabled"
    :size="size"
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
      <div>{{title}}</div>
      <small v-if="subtitle" class="text-muted subtitle">{{subtitle}}</small>
    </template>
    <slot></slot>
    <template v-if="setCustomButtons" #modal-footer>
      <b-button v-for="button in customButtons" 
        :key="button.content" 
        @click="executeFunction(button.action)" 
        :variant="button.variant" 
        :disabled="button.disabled"
        :hidden="button.hidden"
      >
        {{ button.content }}
      </b-button>
    </template>
  </b-modal>
</template>

<script>
  export default {
    props: ["id", "title", "okDisabled", "okOnly", "okTitle", 'setCustomButtons', 'customButtons', 'subtitle', 'size'],
    methods: {
      onEvent(name, event) {
        this.$emit(name, event);
      },
      show() {
        this.$refs.pmModal.show();
      },
      hide() {
        this.$refs.pmModal.hide();
      },
      executeFunction(callback) {
        if (typeof eval(`this.$refs.pmModal.${callback}`) === "function") {
          eval(`this.$refs.pmModal.${callback}`)
        } else {
          this.$emit(callback);
        }
      },
    },
    computed: {
      okTitleWithDefault() {
        return this.okTitle || this.$t('Save');
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
