<template>
  <b-dropdown ref="bDropdown"
              size="sm"
              variant="light"
              split-variant="light"
              split
              class="pm-dds-border"
              :class="{'pm-dds-border-invalid':state===false}">
    <template v-slot:button-content>
      <div class="d-flex align-items-center">
        <b-form-input ref="bFormInput"
                      v-model="text"
                      :placeholder="placeholder"
                      size="sm"
                      class="pm-dds-input"
                      autocomplete="off"
                      @input="onInput"
                      @click="showMenu(sw=!sw)"
                      :state="state">
        </b-form-input>
      </div>
    </template>
    <b-dropdown-item v-for="option in options"
                     :key="option.value"
                     @click="onSelect(option)">
      {{ option.text }}
    </b-dropdown-item>
  </b-dropdown>
</template>

<script>
  export default {
    props: {
      value: {
        type: null,
        required: true
      },
      options: {
        type: Array,
        default: []
      },
      state: {
        type: Boolean,
        default: null
      },
      placeholder: {
        type: String,
        default: ""
      }
    },
    data() {
      return {
        selected: null,
        text: "",
        sw: false
      };
    },
    watch: {
      value: {
        handler(newValue) {
          let option = this.options.find(item => item.value === this.value);
          if (option) {
            this.text = option.text;
            this.selected = option;
          }
        },
        immediate: true
      }
    },
    mounted() {
      document.addEventListener("click", this.onClick);
    },
    destroyed() {
      document.removeEventListener("click", this.onClick);
    },
    methods: {
      onClick(event) {
        if (event.target !== this.$refs.bFormInput.$el) {
          this.showMenu(false);
        }
      },
      onSelect(option) {
        this.selected = option;
        this.text = this.selected.text;
        this.$emit('input', option.value);
        this.$emit("onSelectedOption", option);
        this.showMenu(false);
      },
      onInput(value) {
        this.$emit('onInput', value);
        this.showMenu(true);
      },
      showMenu(sw) {
        let el = this.$refs.bDropdown.$el;
        if (!el) {
          return;
        }
        let obj = el.querySelector(".dropdown-menu").classList;
        if (sw === true) {
          obj.add("pm-dds-show");
        } else {
          obj.remove("pm-dds-show");
        }
      }
    }
  };
</script>

<style>
  .pm-dds-border > button {
    border: 0px;
  }
  .pm-dds-border button:first-child {
    padding: 0px;
    width: 100%;
  }
  .pm-dds-border button:first-child:hover {
    background-color: transparent;
  }
  .pm-dds-show {
    display: block;
    position: absolute;
    transform: translate3d(-1px, 30px, 0px);
    top: 0px;
    left: 0px;
  }
</style>
<style scoped>
  .pm-dds-border {
    border: 1px solid #b6bfc6;
    width: 100%;
    display: flex;
    flex-wrap: nowrap;
  }
  .pm-dds-border-invalid {
    border-color: var(--danger)
  }
  .pm-dds-border:hover {
    box-shadow: 0px 0px 0 3px rgba(8, 114, 194, 0.5);
    border: 1px solid var(--primary) !important;
  }
  .pm-dds-input {
    border-color: transparent;
  }
  .pm-dds-input:focus {
    box-shadow: none !important;
    border-color: transparent !important;
  }
</style>