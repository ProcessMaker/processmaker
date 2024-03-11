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
        <b-button variant="light"
                  size="sm"
                  @click="onRemoveRule"
                  :id="idButtonRemove">
          <img src="/img/trash-fill.svg" :alt="$t('Remove')">
        </b-button>
      </template>
    </PMFloatingButtons>
    <b-popover :target="idButtonRemove"
               triggers="click"
               placement="bottomleft"
               boundary="window"
               :delay="{ show: 100, hide: 1 }"
               custom-class="pm-row-buttons-popover">
      <div class="row p-2">
        <div class="col-auto pb-2">
          {{ $t('Do you want to delete this rule?') }}
        </div>
        <div class="col">
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
        </div>
      </div>
    </b-popover>
  </div>
</template>

<script>
  import PMFloatingButtons from "../../components/PMFloatingButtons.vue";
  export default {
    components: {
      PMFloatingButtons
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
        if (this.$root["inbox-rule-row-button-state"] === true) {
          return;
        }
        this.$refs.pmFloatingButtons.show();
      },
      close() {
        if (this.$root["inbox-rule-row-button-state"] === true) {
          return;
        }
        this.$refs.pmFloatingButtons.close();
      },
      setMargin(size) {
        this.$refs.pmFloatingButtons.$el.style.marginRight = size + "px";
      },
      onEditRule() {
        this.$root["inbox-rule-row-button-state"] = false;
        $emit('onEditRule', this.row);
      },
      onRemoveRule() {
        this.$root["inbox-rule-row-button-state"] = true;
      },
      onDelete() {
        this.$root["inbox-rule-row-button-state"] = false;
        this.$emit('onRemoveRule', this.row);
      },
      onCancel() {
        this.$root["inbox-rule-row-button-state"] = false;
        this.$root.$emit('bv::hide::popover');
        this.close();
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
  .pm-row-buttons-popover {
    max-width: 400px;
  }
</style>