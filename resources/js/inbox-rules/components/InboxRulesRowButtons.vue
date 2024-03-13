<template>
  <div class="pm-row-buttons">
    {{value}}
    <PMFloatingButtons ref="pmFloatingButtons">
      <template v-slot:content>
        <b-button variant="light"
                  size="sm"
                  @click="onEditRule">
          <img src="/img/pencil-fill.svg" :alt="$t('Edit')">
        </b-button>
        <div class="pm-row-buttons-separator"></div>
        <b-button :id="idButtonRemove"
                  variant="light"
                  size="sm"
                  @click="onRemoveRule">
          <img src="/img/trash-fill.svg" :alt="$t('Remove')">
        </b-button>
      </template>
    </PMFloatingButtons>
    <PMPopoverConfirmation :id="idButtonRemove"
                           @onHidden="onHidden">
      <template v-slot:content-message>
        {{ $t('Do you want to delete this rule?') }}
      </template>
      <template v-slot:content-button>
        <b-button variant="secondary"
                  size="sm"
                  @click="onDelete">
          {{ $t('Delete') }}
        </b-button>
        <b-button variant="outline-secondary"
                  size="sm"
                  @click="onCancel">
          {{ $t('Cancel') }}
        </b-button>
      </template>
    </PMPopoverConfirmation>
  </div>
</template>

<script>
  import PMFloatingButtons from "../../components/PMFloatingButtons.vue";
  import PMPopoverConfirmation from "../../components/PMPopoverConfirmation.vue";
  export default {
    components: {
      PMFloatingButtons,
      PMPopoverConfirmation
    },
    props: {
      value: null,
      row: null
    },
    data() {
      return {
        idButtonRemove: "inbox-rule-row-button-" + Math.random().toString(36).substring(2, 9)
      };
    },
    methods: {
      show() {
        this.triggerFloatingButtons(() => {
          this.$refs.pmFloatingButtons.show();
        });
      },
      close() {
        this.triggerFloatingButtons(() => {
          this.$refs.pmFloatingButtons.close();
        });
      },
      setMargin(size) {
        this.$refs.pmFloatingButtons.$el.style.marginRight = size + "px";
      },
      onEditRule() {
        this.disableFloatingButtons(false);
        this.$emit('onEditRule', this.row);
      },
      onRemoveRule() {
        this.disableFloatingButtons(true);
      },
      onDelete() {
        this.disableFloatingButtons(false);
        this.$emit('onRemoveRule', this.row);
      },
      onCancel() {
        this.disableFloatingButtons(false);
        this.$root.$emit('bv::hide::popover');
      },
      onHidden() {
        this.close();
      },
      disableFloatingButtons(state) {
        this.$root["inbox-rule-row-button-floating-disable"] = state;
      },
      triggerFloatingButtons(callback) {
        if (this.$root["inbox-rule-row-button-floating-disable"] === true) {
          return;
        }
        callback();
      }
    }
  };
</script>

<style scoped>
  .pm-row-buttons {
    box-sizing: border-box;
  }
  .pm-row-buttons-separator {
    display: inline;
    border-left: 1px solid #dee2e6;
  }
</style>