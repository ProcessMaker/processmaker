<template>
  <VueMultiselect v-model="input" 
                  :options="options"
                  :placeholder="placeholder"
                  :select-label="selectLabel"
                  :deselect-label="deselectLabel"
                  :selected-label="selectedLabel"
                  :searchable="searchable"
                  :loading="loading"
                  :internal-search="internalSearch"
                  :clear-on-select="clearOnSelect"
                  :close-on-select="closeOnSelect"
                  label="text"
                  track-by="value"
                  @search-change="onSearchChange"
                  class="pm-suggest-list">
  </VueMultiselect>
</template>

<script>
  import VueMultiselect from "@processmaker/vue-multiselect";
  export default {
    components: {
      VueMultiselect
    },
    props: {
      value: {type: null, default: null},
      options: {type: [Array], default: null},
      placeholder: {type: String, default: "Select option"},
      selectLabel: {type: String, default: "Press enter to select"},
      deselectLabel: {type: String, default: "Press enter to remove"},
      selectedLabel: {type: String, default: "Selected"},
      searchable: {type: Boolean, default: true},
      loading: {type: Boolean, default: false},
      internalSearch: {type: Boolean, default: true},
      clearOnSelect: {type: Boolean, default: true},
      closeOnSelect: {type: Boolean, default: true}
    },
    data() {
      return {
        input: null,
        newValue: null
      };
    },
    watch: {
      value: {
        handler(newValue) {
          this.newValue = newValue;
        },
        immediate: true
      },
      options: {
        handler() {
          this.setInput(this.newValue);
        },
        immediate: true,
        deep: true
      },
      input() {
        this.emitInput();
      }
    },
    methods: {
      setInput(value) {
        for (let option of this.options) {
          if (option.value === value) {
            this.input = option;
            break;
          }
        }
      },
      emitInput() {
        this.$emit("input", this.input?.value);
      },
      onSearchChange(searchTerm) {
        this.$emit("onSearchChange", searchTerm);
      }
    }
  };
</script>

<style scoped>
  .pm-suggest-list{
    min-height: 38px;
  }
</style>
<style>
  .pm-suggest-list>div{
    padding: 0px;
    margin: 0px;
  }
  .pm-suggest-list>.multiselect__select{
    height: 40px;
  }
  .pm-suggest-list>.multiselect__tags{
    display: flex;
    align-items: center;
    justify-content: flex-start;
    min-height: 38px;
  }
  .pm-suggest-list>.multiselect__tags>*{
    padding: 0px;
    margin: 0px 40px 0px 5px;
  }
  .pm-suggest-list>.multiselect__tags>.multiselect__placeholder{
    font-size: 16px;
  }
  .pm-suggest-list>.multiselect__content-wrapper{
    font-size: 14px;
  }
</style>