import { ref, onUnmounted } from "vue";

export default {};
/**
 * This composable only works with columns in AppTable
 * @param {*} column is a ref variable, come from App Table
 * @param {*} tableName
 * @returns
 */
export const columnResizeComposable = ({ column, stopResize }) => {
  const startX = ref(0);
  const startWidth = ref(0);
  const isResizing = ref(false);

  const minWidth = 144;

  // Resize the column value
  const doResize = (event) => {
    if (isResizing.value) {
      const diff = event.pageX - startX.value;
      const min = minWidth;
      const currentWidth = Math.max(min, startWidth.value + diff);
      column.width = currentWidth;
    }
  };

  const stopResizeHandler = () => {
    if (isResizing.value) {
      document.removeEventListener("mousemove", doResize);
      document.removeEventListener("mouseup", stopResizeHandler);
      isResizing.value = false;
      stopResize?.();
    }
  };

  // Init the events in mousemove and finish in mouseup event
  const startResize = (event, index) => {
    isResizing.value = true;
    startX.value = event.pageX;
    startWidth.value = column.width || minWidth;

    document.addEventListener("mousemove", doResize);
    document.addEventListener("mouseup", stopResizeHandler);
  };

  onUnmounted(() => {
    document.removeEventListener("mousemove", doResize);
    document.removeEventListener("mouseup", stopResizeHandler);
  });

  return {
    startResize,
    stopResizeHandler,
  };
};
