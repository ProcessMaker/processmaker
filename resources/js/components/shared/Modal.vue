<template>
  <b-modal
    :id="id"
    ref="pmModal"
    :title="title"
    footer-class="pm-modal-footer"
    cancel-variant="outline-secondary"
    :cancel-title="$t('Cancel')"
    :ok-variant="okVariant ? okVariant : 'secondary'"
    :ok-title="okTitleWithDefault"
    :ok-disabled="okDisabled"
    :hide-footer="hideFooter"
    :hide-header="hideHeader"
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
          <div class="w-100 p-0" :class="hasHeaderButtons ? 'd-flex justify-content-between align-middle align-items-center' : ''">
            <b-button v-show="hasHeaderButtons" v-for="button in headerButtons" :key="button.content"
              :aria-label="button.ariaLabel"
              :variant="button.variant"
              :disabled="button.disabled"
              :hidden="button.hidden"
              size="sm"
              class="pl-0"
              @click="executeFunction(button.action)"
            >
              <small> {{ $t(button.content) }}</small>
            </b-button>
            <b-button variant="link" @click="close()" class="close">×</b-button>
          </div>
        </div>
        <div v-if="hasTitleButtons">
          <div class="d-flex justify-content-between align-middle align-items-center w-100 pt-3">
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
              size="sm"
              @click="executeFunction(button.action)"
            >
              <i v-if="button.icon" :class="button.icon" /> {{ button.content }}
            </b-button>
          </div>
        </div>
      </div>
    </template>

    <template #modal-title v-else>
      <div>
        <i
          v-if="titleIcon"
          class="pr-1 fa-fw"
          :class="titleIcon"
        />
        {{ title }}
      </div>
      <small v-if="subtitle" class="text-muted subtitle mt-1">{{subtitle}}</small>
    </template>
    <slot></slot>
    <template v-if="setCustomButtons" #modal-footer>
      <div class="d-flex align-items-center w-100"
        :class="{'justify-content-end': !showAiSlogan || !requiredInFooter, 'justify-content-between': showAiSlogan || requiredInFooter}">
        <div>
          <div v-if="requiredInFooter">
            <required class="required-footer"></required>
          </div>
          <div v-if="showAiSlogan" class="slogan">
            <img src="/img/favicon.svg"> {{ $t("Powered by ProcessMaker AI") }}
          </div>
        </div>
        <div>
          <b-button v-for="button in customButtons"
            :key="button.content"
            @click="executeFunction(button.action)"
            :variant="button.variant"
            :disabled="button.disabled"
            :hidden="button.hidden"
            :size="button.size"
            :data-test="button.dataTest"
            class="ml-2"
          >
            {{ button.content }}
          </b-button>
        </div>
      </div>
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
      "okTitle",
      "okVariant",
      "setCustomButtons",
      "customButtons",
      "subtitle",
      "size",
      "hideFooter",
      "hideHeader",
      "hasHeaderButtons",
      "headerButtons",
      "hasTitleButtons",
      "titleButtons",
      "showAiSlogan",
      "requiredInFooter",
      "titleIcon"
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
  .slogan {
    font-size: 80%;
    font-weight: 600;
  }

  .slogan img {
    display: inline-block;
    height: 16px;
  }

  .required-footer {
    text-align: left !important;
  }
</style>
