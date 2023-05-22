export default {
  data() {
    return {
      focusErrors: null,
    };
  },
  mounted() {
    // Listen only on the root Vue instance
    if (!this.$parent) {
      // Set the focus within any modal or popover that is instantiated
      this.$root.$on("bv::modal::shown", this.setFocusWithin);
      this.$root.$on("bv::popover::shown", this.setFocusWithin);
    }

    if (this.focusErrors) {
      // watch an object for form errors
      this.$watch(this.focusErrors, this.focusErrorsChanged, { deep: true });
      this.dontListenForApiClientError();
    } else {
      // default api error focusing
      this.listenForApiClientError();
    }
  },
  methods: {
    /**
     * Sets the focused element within the most recently
     * opened modal or popover component.
     * @param shownEvent
     * @param modalId
     */
    setFocusWithin(shownEvent, modalId) {
      if (!(shownEvent.target instanceof HTMLElement)
          && !(shownEvent.relatedTarget instanceof HTMLElement)) {
        return;
      }

      // If relatedTarget is present and an HTMLElement,
      // then we know we're working with a popover,
      // otherwise it's a modal
      const target = shownEvent.relatedTarget instanceof HTMLElement
        ? shownEvent.relatedTarget
        : shownEvent.target;

      // Find the first focusable element that isn't the
      // modal/popover close button
      const focusableElement = target.querySelector("input, select, textarea, button:not(.close), a");

      // If there is an element to focus on, then do so
      if (focusableElement instanceof HTMLElement) {
          //do not set focus() if it is a vue-multiselect
          const parentVue = this.findHtmlElementParentVueComponent(focusableElement);
          if (parentVue !== null && parentVue.$options.name == 'vue-multiselect') {
              return;
          }
        focusableElement.focus()
      }
    },
    findHtmlElementParentVueComponent(element) {
        if(element === undefined) {
            return null; 
        }
        if ('__vue__' in element) {
            return element.__vue__; 
        }
        else {
            return this.findHtmlElementParentVueComponent(element.parentNode);
        }
    },
    hasCustomFocusErrors() {
      if (this.$root._hasCustomFocusErrors) {
        this.$off;
      }
    },
    listenForApiClientError() {
      if (typeof window.ProcessMaker._focusErrorsIntitalized === "undefined") {
        window.ProcessMaker.EventBus.$on("api-client-error", this.onApiClientError);
        window.ProcessMaker._focusErrorsIntitalized = true;
      }
    },
    dontListenForApiClientError() {
      window.ProcessMaker.EventBus.$off("api-client-error", this.onApiClientError);
      window.ProcessMaker._focusErrorsIntitalized = true;
    },
    onApiClientError(error) {
      const errors = _.get(error, "response.data.errors", false);
      if (errors) {
        this.focusErrorsChanged(errors);
      }
    },
    focusErrorsChanged(newValue) {
      const selector = Object.entries(newValue)
        .filter(([_, value]) => value !== null) // Filter out null values
        .map(([field, _]) => `[name='${field}']`) // Select elements matching the name attribute
        .join(", ");

      if (!selector) {
        return;
      }

      const firstInput = document.querySelector(selector); // Find the first match
      if (firstInput) {
        firstInput.focus();
      }
    },
  },
};
