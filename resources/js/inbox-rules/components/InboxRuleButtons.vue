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
                id="inboxRuleButtonsDropdown"
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
    <b-popover target="inboxRuleButtonsDropdown"
               triggers="hover focus"
               placement="bottom"
               boundary="window"
               show
               v-if="showMessageEmpty">
      <div class="mt-2 mb-2 ml-3 mr-3">
        {{ $t('No saved searches available') }}
      </div>
    </b-popover>
    <b-popover :show.sync="popoverMessage"
               target="inboxRuleButtonsDropdown"
               triggers=""
               placement="bottom"
               boundary="window"
               custom-class="inbox-rule-buttons-popover-message border border-danger">
      <div class="mt-2 mb-2 ml-3 mr-3">
        <b-icon icon="exclamation-circle"
                variant="danger"
                aria-hidden="true"></b-icon>
        <span class="text-danger">
          {{ $t('Select a saved search.') }}
        </span>
      </div>
    </b-popover>
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
        options: [],
        showMessageEmpty: false,
        popoverMessage: false
      };
    },
    watch: {
      options() {
        this.showMessageEmpty = this.options.length <= 0;
      }
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
      },
      showPopoverMessage() {
        this.popoverMessage = true;
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
  .inbox-rule-buttons-popover-message .arrow::before{
    border-bottom-color: #e50130;
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