/**
 * Accessibility-related mixins
 */
export default {
  methods: {
    setFocusWithin(shownEvent) {
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
