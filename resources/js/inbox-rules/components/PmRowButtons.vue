<template>
  <div class="pm-row-buttons">
    {{value}}
    <PMFloatingButtons ref="pmFloatingButtons">
      <template v-slot:content>

        <b-button variant="light"
                  size="sm"
                  @click="$emit('onEditRule', row);">
          <img src="/img/pencil-fill.svg" :alt="$t('Edit')">
        </b-button>

        <div class="pm-row-buttons-separator"></div>

        <b-button variant="light"
                  size="sm"
                  @click="$emit('onRemoveRule', row)">
          <img src="/img/trash-fill.svg" :alt="$t('Remove')">
        </b-button>

      </template>
    </PMFloatingButtons>
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
    methods: {
      show() {
        this.$refs.pmFloatingButtons.show();
      },
      close() {
        this.$refs.pmFloatingButtons.close();
      },
      setMargin(size) {
        this.$refs.pmFloatingButtons.$el.style.marginRight = size + "px";
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