<template>
  <div class="pm-task-row-buttons">
    <PMFloatingButtons ref="pmFloatingButtons">
      <template v-slot:content>
        <slot name="body">
          <span v-for="(button, index) in buttons" :key="index">
            <b-button :id="button.id + rowIndex"
                      v-if="button.show"
                      aria-label="button.title"
                      @click="onClick(button)"
                      variant="light"
                      size="sm">
              <i v-if="button.icon" 
                 :class="button.icon"/>
              <img v-else-if="button.imgSrc"
                   :src="button.imgSrc"
                   :alt="button.title"/>
            </b-button>
            <b-tooltip
              :target="button.id + rowIndex"
              :title="button.title"
              custom-class="task-hover-tooltip"
              placement="bottom"
              :delay="0"
              boundary="viewport"
              :no-fade="true"
              />
            <div v-if="index < buttons.length - 1 && button.show" 
                 class="task-vertical-separator">
            </div>
          </span>
        </slot>
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
      buttons: null,
      row: null,
      rowIndex: null,
      colIndex: null,
      showButtons: null
    },
    methods: {
      show() {
        this.triggerFloatingButtons(() => {
          if (!this.showButtons) {
            return;
          }
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
      disableFloatingButtons(state) {
        this.$root["inbox-rule-row-button-floating-disable"] = state;
      },
      triggerFloatingButtons(callback) {
        if (this.$root["inbox-rule-row-button-floating-disable"] === true) {
          return;
        }
        callback();
      },
      onClick(button) {
        this.disableFloatingButtons(false);
        button.click(this.row);
      }
    }
  };
</script>

<style scoped>
  .pm-task-row-buttons {
    position: relative;
    display: math;
  }
  .task-vertical-separator {
    display: inline;
    border-left: 1px solid #ccc;
    margin-left: 4px;
    margin-right: 4px;
    vertical-align: middle;
  }
</style>
<style>
  .task-hover-tooltip {
    opacity: 1 !important;
  }
  .task-hover-tooltip .tooltip-inner {
    background-color: #F2F6F7;
    color: #556271;
    box-shadow: -5px 5px 5px rgba(0, 0, 0, 0.3);
    border-radius: 7px;
    padding: 9px 12px 9px 12px;
    border: 1px solid #CDDDEE
  }
  .task-hover-tooltip .arrow::before {
    border-bottom-color: #CDDDEE !important;
  }
  .task-hover-tooltip .arrow::after {
    content: "";
    position: absolute;
    bottom: 0;
    border-width: 0 .4rem .4rem;
    transform: translateY(3px);
    border-color: transparent;
    border-style: solid;
    border-bottom-color: #F2F6F7;
  }
</style>