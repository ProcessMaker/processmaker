import {
  ref, onUnmounted
} from "vue";

export default {};
/**
 * This composable only works with columns in AppTable
 * @param {*} column is a ref variable, come from App Table
 * @param {*} tableName
 * @returns
 */
export const columnResizeComposable = (column) => {
  const startX = ref(0);
  const startWidth = ref(0);
  const isResizing = ref(false);

  //Resize the column value
  const doResize = (event) => {
   
    if (isResizing.value) {
      const diff = event.pageX - startX.value;
      const min = 63;
      const currentWidth = Math.max(min, startWidth.value + diff);
   
      column.width = currentWidth;
    }
  };

  const stopResize = () => {
    if (isResizing.value) {
      document.removeEventListener("mousemove", doResize);
      document.removeEventListener("mouseup", stopResize);
      isResizing.value = false;
    }
  };

  // Init the events in mousemove and finish in mouseup event
  const startResize = (event, index) => {
    isResizing.value = true;
    startX.value = event.pageX;
    startWidth.value =column.width || 200;
   
    document.addEventListener("mousemove", doResize);
    document.addEventListener("mouseup", stopResize);
  };

  onUnmounted(() => {
    document.removeEventListener("mousemove", doResize);
    document.removeEventListener("mouseup", stopResize);
  });

  return {
    startResize,
  };
};
