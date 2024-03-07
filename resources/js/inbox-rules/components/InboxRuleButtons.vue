<template>
  <div>
    <b-button size="sm"
              variant="light"
              class="button-border">
      <img src="/img/eraser-fill.svg">
        {{ $t('Clear filters') }}
    </b-button>
    <b-dropdown v-if="showSavedSearchSelector"
                @input="$emit('saved-search-id-changed', $event)"
                size="sm"
                split
                split-variant="light"
                variant="light"
                text="Load a saved search">
      <b-dropdown-item href="#">Action</b-dropdown-item>
      <b-dropdown-item href="#">Another action</b-dropdown-item>
    </b-dropdown>
    <b-button size="sm"
              variant="light"
              class="button-border"
              @click="$emit('showColumns')"
              >
      <img src="/img/gear-fill.svg">
    </b-button>
  </div>
</template>

<script>
  export default {
    components: {
    },
    props: {
      showSavedSearchSelector: {
        type: Boolean,
        default: true
      }
    },
    data() {
      return {
      };
    },
    mounted() {
    },
    methods: {
    }
  };
</script>

<style scoped>
  .button-border {
    border: 1px solid #CDDDEE;
    box-shadow: 0 0 5px #CDDDEE;
  }
</style>