import mitt from "mitt";

/**
 * Vue 3–compatible event emitter (replaces new Vue() buses and $on/$off/$emit).
 */
export function createPmEventBus() {
  const emitter = mitt();
  return {
    $on: (type, handler) => emitter.on(type, handler),
    $off: (type, handler) => emitter.off(type, handler),
    $emit: (type, payload) => emitter.emit(type, payload),
    /** Raw mitt instance for new code */
    emitter,
  };
}
