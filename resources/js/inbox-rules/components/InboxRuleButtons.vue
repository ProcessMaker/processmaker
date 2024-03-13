<template>
  <div>
    <b-button size="sm"
              variant="light"
              class="button-border button-font">
      <img src="/img/eraser-fill.svg" :alt="$t('Clear filters')">
        {{ $t('Clear filters') }}
    </b-button>
    <b-dropdown v-if="showSavedSearchSelector"
                @input="$emit('saved-search-id-changed', $event)"
                size="sm"
                split
                split-variant="light"
                variant="light"
                text="Load a saved search"
                class="button-border">
      <template v-slot:button-content>
        <img src="/img/funnel-fill.svg" :alt="$t('Load a saved search')">
          <span class="button-font">
            {{ $t('Load a saved search') }}
          </span>
      </template>
      <b-dropdown-item href="#">Action</b-dropdown-item>
      <b-dropdown-item href="#">Another action</b-dropdown-item>
    </b-dropdown>
    <b-button size="sm"
              variant="light"
              class="button-border button-font"
              @click="$emit('showColumns')">
      <img src="/img/gear-fill.svg" :alt="$t('Configure')">
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

<style>
  .button-border > button {
    border-top: 0px;
    border-bottom: 0px;
  }
</style>
<style scoped>
  .button-border {
    border: 1px solid #CDDDEE;
    box-shadow: 0 0 5px #CDDDEE;
  }
  .button-font {
    text-transform: none;
  }
</style>