<template>
  <div>
    <div class="position-relative">
      <button ref="optionsButton" class="btn btn-outline-secondary mr-1 mt-3 d-flex align-items-center" @click="toggleOptionsPopup">
        <font-awesome-icon :icon="['fpm', 'fa-translations']" class="mr-1" />
        <span class="text-capitalize">{{$t('Translation Options')}}</span>
      </button>
      <div v-if="showOptionsPopup" class="filter-dropdown-panel-container card" v-click-outside="closeOptionsPopup">
        <div class="card-body">
          <b-form-group>
            <b-form-radio
              class="mb-3"
              v-model="selectedTranslateOption"
              :key="'all'"
              value="all"
              :disabled="false"
            >
              <span class="fw-medium">{{ $t('Auto Translate') }} <strong>{{ $t('All') }}</strong></span>
              <div>
                <small class="text-muted">
                  Automatically translate all strings for this screen. All manual translation changes will be overwritten.
                </small>
              </div>
            </b-form-radio>

            <b-form-radio
              v-model="selectedTranslateOption"
              :key="'empty'"
              value="empty"
              :disabled="false"
            >
              <span class="fw-medium">{{ $t('Auto Translate') }} <strong>{{ $t('Empty') }}</strong></span>
              <div>
                <small class="text-muted">
                  Automatically translate any empty fields for this screen.
                </small>
              </div>
            </b-form-radio>
          </b-form-group>
        </div>
        <div class="card-footer bg-white text-right">
          <button class="btn btn-white btn-sm" @click="toggleOptionsPopup">Cancel</button>
          <button class="btn btn-primary btn-sm" @click="reTranslate">Translate</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>

let myEvent;
export default {
  directives: {
    clickOutside: {
      bind(el, binding, vnode) {
        myEvent = function (event) {
          if (!(el === event.target || el.contains(event.target) || vnode.context.$refs.optionsButton.contains(event.target))) {
            vnode.context[binding.expression](event);
          }
        };
        document.body.addEventListener("click", myEvent);
      },
      unbind() {
        document.body.removeEventListener("click", myEvent);
      },
    },
  },
  props: [
  ],
  data() {
    return {
      showOptionsPopup: false,
      selectedTranslateOption: null,
    };
  },

  methods: {
    closeOptionsPopup() {
      this.showOptionsPopup = false;
    },

    toggleOptionsPopup() {
      this.showOptionsPopup = !this.showOptionsPopup;
    },

    reTranslate() {
      this.$emit("retranslate", this.selectedTranslateOption);
      this.showOptionsPopup = false;
    },
  },
};
</script>

<style lang="scss">
.advanced-search {
  .multiselect__placeholder {
    padding-top: 1px;
  }

  .multiselect,
  .multiselect__input,
  .multiselect__single {
    font-size: 14px;
  }

  .multiselect__input {
    left: 1px;
    padding: 2px 0 2px 10px;
    position: absolute;
    top: 8px;
  }

  .multiselect--active {
    .multiselect__input {
      width: 99% !important;
    }
  }

  .multiselect__single {
    padding-bottom: 2px;
    padding-top: 2px;
  }

  .group {
    position: relative;
    background-color: #ffffff;
    color: #b6bfc6;
    border-radius: 2px;
  }
}

.filter-dropdown-panel-container {
  min-width: 20rem;
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.125);
  box-shadow: 0 6px 12px 2px rgba(0, 0, 0, 0.168627451);
  position: absolute;
  left: 0;
  top: 2.8rem;
  border-radius: 3px;
  z-index: 1;
  max-width: 30rem;
}
</style>
