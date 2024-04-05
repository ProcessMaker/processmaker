<template>
  <div>
    <b-button size="sm"
              variant="light"
              class="inbox-rule-buttons-border inbox-rule-buttons-font"
              @click="$emit('reset-filters')">
      <img src="/img/eraser-fill.svg" :alt="$t('Clear unsaved filters')"/>
      {{ $t('Clear unsaved filters') }}
    </b-button>
    <b-dropdown v-if="showSavedSearchSelector"
                ref="inboxRuleButtonsDropdown"
                id="inboxRuleButtonsDropdown"
                size="sm"
                variant="light"
                split-variant="light"
                split
                class="inbox-rule-buttons-border rounded-sm">
      <template v-slot:button-content>
        <div class="d-flex align-items-center">
          <img src="/img/funnel-fill.svg" :alt="$t('Load a saved search')"/>
          <b-form-input v-model="selectedText"
                        :placeholder="selectedOption ? selectedOption.text : $t('Load a saved search')"
                        size="sm"
                        class="inbox-rule-buttons-input"
                        autocomplete="off"
                        @input="onInput">
          </b-form-input>
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
               placement="top"
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
              class="inbox-rule-buttons-border inbox-rule-buttons-font"
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
        selectedText: "",
        options: [],
        showMessageEmpty: false,
        popoverMessage: false
      };
    },
    watch: {
      options() {
        this.showMessageEmpty = this.options.length <= 0;
      },
      selectedOption() {
        this.selectedText = this.selectedOption?.text;
      }
    },
    mounted() {
      this.requestSavedSearch("");
    },
    methods: {
      onSelect(option) {
        this.selectedOption = option;
        this.$emit('saved-search-id-changed', option.value);
        this.showMenu(false);
      },
      requestSavedSearch(filter) {
        let url = "saved-searches" +
                "?include=reports,user_options" +
                "&page=1" +
                "&per_page=30" +
                "&filter=" + filter +
                "&type=task" +
                "&key=" +
                "&is_system=false" +
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
      },
      onInput(value) {
        this.requestSavedSearch(value);
        this.showMenu(true);
      },
      showMenu(sw) {
        let obj = document.getElementById("inboxRuleButtonsDropdown")
                .querySelector(".dropdown-menu")
                .classList;
        if (sw === true) {
          obj.add("inbox-rule-buttons-show");
        } else {
          obj.remove("inbox-rule-buttons-show");
        }
      }
    }
  };
</script>

<style>
  .inbox-rule-buttons-border > button {
    border-top: 0px;
    border-bottom: 0px;
  }
  .inbox-rule-buttons-border button:first-child {
    min-width: 150px;
    padding-top: 0px;
    padding-bottom: 0px;
  }
  .inbox-rule-buttons-popover-message .arrow::before{
    border-bottom-color: #e50130;
  }
  .inbox-rule-buttons-show {
    display: block;
    position: absolute;
    transform: translate3d(-1px, 30px, 0px);
    top: 0px;
    left: 0px;
    will-change: transform;
  }
</style>
<style scoped>
  .inbox-rule-buttons-border {
    border: 1px solid #CDDDEE;
    box-shadow: 0 0 5px #CDDDEE;
  }
  .inbox-rule-buttons-font {
    text-transform: none;
  }
  .inbox-rule-buttons-input {
    border: 1px solid white;
    padding-top: 0px;
    padding-bottom: 0px;
    height: auto;
    width: 150px;
  }
  .inbox-rule-buttons-input:focus {
    box-shadow: none !important;
    border: 1px solid white !important;
  }
</style>