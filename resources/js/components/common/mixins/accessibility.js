export default {
  mounted() {
    // Listen only on the root Vue instance
    if (!this.$parent) {
      this.$root.$on('bv::modal::shown', this.setFocusWithin)
      this.$root.$on('bv::popover::shown', this.setFocusWithin)
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
      if (!(shownEvent['target'] instanceof HTMLElement) &&
          !(shownEvent['relatedTarget'] instanceof HTMLElement)) {
        return
      }

      // If relatedTarget is present and an HTMLElement,
      // then we know we're working with a popover,
      // otherwise it's a modal
      const target = shownEvent['relatedTarget'] instanceof HTMLElement
        ? shownEvent.relatedTarget
        : shownEvent.target;

      // Find the first focusable element that isn't the
      // modal/popover close button
      const focusableElement = target.querySelector('input, select, textarea, button:not(.close), a')

      // If there is an element to focus on, then do so
      if (focusableElement instanceof HTMLElement) {
        focusableElement.focus()
      }
    },
  }
}
