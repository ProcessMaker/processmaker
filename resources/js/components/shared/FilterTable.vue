<template>
  <div class="table-resizable">
    <table class="filter-table">
      <thead>
        <tr>
          <th class="border" :colspan="headers.length"></th>
        </tr>
        <tr>
          <th
            class="ellipsis-column"
            v-for="(column, index) in headers"
            :key="index"
            :class="{ 'sortable-column': column.sortable }"
          >
            <div
              class="column-header"
              :style="{ width: column.width + 'px' }"
            >
              <slot :name="column.field">
                {{ column.label }}
                <PMColumnFilterPopover :id="'pm-table-column-'+index" :container="''"></PMColumnFilterPopover>
              </slot>
            </div>
            <div
              v-if="index !== headers.length - 1"
              class="column-resizer"
              @mousedown="startResize(index)"
            >
            </div>
          </th>
        </tr>
        <tr>
          <th class="border" :colspan="headers.length"></th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="(row, rowIndex) in data.data"
          :key="rowIndex"
          @click="handleRowClick(row)"
        >
          <slot :name="`row-${rowIndex}`">
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
          </slot>
        </tr>
      </tbody>
    </table>
  </div>
  </template>

<script>

import AvatarImage from "../../components/AvatarImage.vue"
import moment from "moment";
import PMColumnFilterPopover from "../PMColumnFilterPopover/PMColumnFilterPopover.vue";

export default {
  components: {
    AvatarImage,
    PMColumnFilterPopover,
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
  watch: {
    data() {
      this.headers.forEach(column => {
        if(column.format) {
          if (column.format === 'datetime' || column.format === 'date') {
            this.data.data.forEach(element => {
              element[column.field] = this.formatDate(element[column.field], column.format);
            });
          }
        }
      });
    },
  },
  mounted() {
    this.$nextTick(() => {
      const ellipsisColumn = document.querySelectorAll('.ellipsis-column');

      ellipsisColumn.forEach((column) => {
        column.addEventListener('click', this.handleEllipsisClick);
      });
    });
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
    formatDate(date, mask) {
      if (mask === 'datetime') {
        return date === null ? "-" : moment(date).format("MM/DD/YY HH:mm");
      }
      if (mask === 'date') {
        return date === null ? "-" : moment(date).format("MM/DD/YY");
      }
    },
    handleEllipsisClick(event) {
      this.$emit('table-elipsis-click', event);
    },
    handleRowClick(row) {
      this.$emit('table-row-click', row);
    },
  },
};
</script>

<style>
.table-resizable {
  overflow-x: auto;
  max-height: 400px;
  overflow-y: auto;
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
  max-height: 400px;
  border-collapse: collapse;
  border-left: 1px solid rgba(0, 0, 0, 0.125);
  border-right: 1px solid rgba(0, 0, 0, 0.125);
  position: relative;
}
.filter-table td {
  border-top: 1px solid rgba(0, 0, 0, 0.125);
  border-bottom: 1px solid rgba(0, 0, 0, 0.125);
  padding: 10px 16px;
}
.ellipsis-column {
  padding: 10px 16px;
}
.filter-table th:hover {
  background-color: #FAFBFC;
  color: #1572C2;
}
.filter-table tbody tr:hover {
  background-color: #FAFBFC;
  color: #1572C2;
}
.filter-table thead {
  position: sticky;
  top: 0;
  background-color: #fff;
}
.filter-table .sortable-column:hover::after {
  content: '\2026';
  position: absolute;
  top: 50%;
  right: 7px;
  transform: translateY(-50%) rotate(90deg);
  font-size: 16px;
  line-height: 1;
  cursor: pointer;
}
.border {
  height: 1px;
  padding: 0 !important;
  background-color: rgba(0, 0, 0, 0.125);
  border: 0 !important;
}
</style>
