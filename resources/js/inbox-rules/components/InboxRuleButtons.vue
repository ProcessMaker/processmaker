<template>
  <div>
    <b-button size="sm"
              variant="light"
              class="button-border button-font"
              @click="$emit('reset-filters')">
      <img src="/img/eraser-fill.svg" :alt="$t('Clear unsaved filters')"/>
      {{ $t('Clear unsaved filters') }}
    </b-button>
    <b-dropdown v-if="showSavedSearchSelector"
                size="sm"
                variant="light"
                split-variant="light"
                split
                class="button-border">
      <template v-slot:button-content>
        <div class="text-left">
          <img src="/img/funnel-fill.svg" :alt="$t('Load a saved search')"/>
          <span class="button-font">
            {{ selectedOption ? selectedOption.text : $t('Load a saved search') }}
          </span>
        </div>
      </template>
      <b-dropdown-item v-for="option in options"
                       :key="option.value" 
                       :value="option"
                       @click="onSelect(option)">
        {{ option.text }}
      </b-dropdown-item>
    </b-dropdown>
    <b-button size="sm"
              variant="light"
              class="button-border button-font"
              @click="$emit('showColumns')">
      <img src="/img/gear-fill.svg" :alt="$t('Configure')"/>
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
        selectedOption: null,
        options: []
      };
    },
    mounted() {
      this.requestSavedSearch("");
    },
    methods: {
      onSelect(option) {
        this.selectedOption = option;
        this.$emit('saved-search-id-changed', option.value);
      },
      requestSavedSearch(filter) {
        let url = "saved-searches" +
                "?include=reports,user_options" +
                "&page=1" +
                "&per_page=10" +
                "&filter=" + filter +
                "&type=task" +
                "&subset=mine" +
                "&order_by=title" +
                "&order_direction=asc";
        ProcessMaker.apiClient.get(url)
                .then(response => {
                  this.options = [];
                  response?.data?.data?.forEach(item => {
                    this.options.push({
                      text: item.title,
                      value: item.id
                    });
                  });
                });
      }
    }
  };
</script>

<style>
  .button-border > button {
    border-top: 0px;
    border-bottom: 0px;
  }
  .button-border button:first-child {
    min-width: 172px;
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