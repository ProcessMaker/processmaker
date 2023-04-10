<template>
  <b-modal
    :id="id"
    ref="pmModal"
    :title="title"
    footer-class="pm-modal-footer"
    cancel-variant="outline-secondary"
    :cancel-title="$t('Cancel')"
    ok-variant="secondary"
    :ok-title="okTitleWithDefault"
    :ok-disabled="okDisabled"
    :hide-footer="hideFooter"
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
    <template #modal-header="{ close }" v-if="hasHeaderButtons || hasTitleButtons">
      <div class="w-100 d-block">
        <div>
          <div class="w-100 p-0" :class="hasHeaderButtons ? 'd-flex justify-content-between align-middle' : ''">
            <b-button v-show="hasHeaderButtons" v-for="button in headerButtons" :key="button.content" 
              :aria-label="button.ariaLabel"
              :variant="button.variant"
              :disabled="button.disabled"
              :hidden="button.hidden"
              @click="executeFunction(button.action)"
            >
              <small> {{ $t(button.content) }}</small>
            </b-button>
            <b-button variant="link" @click="close()" class="close">×</b-button>
          </div>
        </div>
        <div v-if="hasTitleButtons">
          <div class="d-flex justify-content-between align-middle w-100 pt-3">
            <h5>
              {{ title }}
              <small v-if="subtitle" class="text-muted subtitle d-block mt-1">{{subtitle}}</small>
            </h5>
            <b-button v-for="(button, index) in titleButtons" 
              :key="button.content" 
              :aria-label="button.ariaLabel" 
              :hidden="button.hidden"
              :disabled="button.disabled"
              :variant="button.variant" 
              :class="button.position"
              @click="executeFunction(button.action)" 
            >
              <i v-if="button.icon" :class="button.icon" /> {{ button.content }}
            </b-button>
          </div>
        </div>
      </div>
    </template>

    <template #modal-title v-else>
      <div>{{title}}</div>
      <small v-if="subtitle" class="text-muted subtitle mt-1">{{subtitle}}</small>
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
    props: [
      "id",
      "title", 
      "okDisabled", 
      "okOnly", 
      "okTitle", "setCustomButtons",
      "customButtons", 
      "subtitle", 
      "size", 
      "hideFooter", 
      "hasHeaderButtons", 
      "headerButtons", 
      "hasTitleButtons", 
      "titleButtons"
    ],
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
