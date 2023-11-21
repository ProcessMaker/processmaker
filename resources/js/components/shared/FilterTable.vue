<template>
  <div class="table-resizable">
    <table class="filter-table">
      <thead>
        <tr>
          <th
            v-for="(column, index) in headers"
            :key="index"
          >
            <div
              class="column-header"
              :style="{ width: column.width + 'px' }"
            >
              {{ column.label }}
            </div>
            <div
              v-if="index !== headers.length - 1"
              class="column-resizer"
              @mousedown="startResize(index)"
            >
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(row, rowIndex) in data.data"
          :key="rowIndex"
        >
          <td
            v-for="(header, index) in headers"
            :key="index"
          >
            <div v-if="containsHTML(row[header.field])" v-html="row[header.field]"></div>
            <template v-else>
              <template v-if="isComponent(row[header.field])">
                <component 
                  :is="row[header.field].component"
                  v-bind="row[header.field].props"
                >
                </component>
              </template>
              <template v-else>
                {{ row[header.field] }}
              </template>
            </template>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  </template>

<script>

import AvatarImage from "../../components/AvatarImage.vue"

export default {
  components: {
    AvatarImage
  },
  props: {
    headers: [],
    data: [],
  },
  data() {
    return {
      hoveredColumn: null,
      isResizing: false,
      startX: 0,
      startWidth: 0,
      resizingColumnIndex: -1,
    };
  },
  mounted() {

  },
  methods: {
    containsHTML(text) {
      const doc = new DOMParser().parseFromString(text, 'text/html');
      return Array.from(doc.body.childNodes).some(node => node.nodeType === Node.ELEMENT_NODE);
    },
    isComponent(content) {
      if (content && typeof content === 'object') {
        return content.component && typeof content.props === 'object';
      }
    },
    startResize(index) {
      this.isResizing = true;
      this.resizingColumnIndex = index;
      this.startX = event.pageX;
      this.startWidth = this.headers[index].width;

      document.addEventListener('mousemove', this.doResize);
      document.addEventListener('mouseup', this.stopResize);
    },
    doResize(event) {
      if (this.isResizing) {
        const diff = event.pageX - this.startX;
        this.headers[this.resizingColumnIndex].width = Math.max(
          40,
          this.startWidth + diff
        );
      }
    },
    stopResize() {
      if (this.isResizing) {
        document.removeEventListener('mousemove', this.doResize);
        document.removeEventListener('mouseup', this.stopResize);
        this.isResizing = false;
        this.resizingColumnIndex = -1;
      }
    },
  },
  watch: {
    data() {
      console.log(this.data)
    }
  },
};
</script>

<style>
.table-resizable {
  overflow-x: auto;
}

.table-resizable th {
  position: relative;
  padding: 8px;
}

.column-header {
  overflow: hidden;
  white-space: nowrap;
}

.column-resizer {
  position: absolute;
  right: -5px;
  top: 50%;
  transform: translateY(-50%);
  height: 30px;
  width: 10px;
  cursor: col-resize;
  border-left: 1px solid rgba(0, 0, 0, 0.125);
}
.filter-table {
  width: 100%;
  border-collapse: collapse;
  border-left: 1px solid rgba(0, 0, 0, 0.125);
  border-right: 1px solid rgba(0, 0, 0, 0.125);
}
.filter-table th,
.filter-table td {
  border-top: 1px solid rgba(0, 0, 0, 0.125);
  border-bottom: 1px solid rgba(0, 0, 0, 0.125);
  padding: 8px; /* Ajusta el relleno si es necesario */
}
</style>
