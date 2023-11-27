<template>
  <div class="pm-table-container">
    <table class="pm-table-filter">
      <thead>
        <tr>
          <th class="pm-table-border" :colspan="headers.length"></th>
        </tr>
        <tr>
          <th
            class="pm-table-ellipsis-column"
            v-for="(column, index) in headers"
            :key="index"
          >
            <div
              class="pm-table-column-header"
              :style="{ width: column.width + 'px' }"
            >
              <slot :name="column.field">
                {{ column.label }}
              </slot>
            </div>
            <div class="pm-table-filter-button">
              <slot :name="`filter-${column.field}`">
                
              </slot>
            </div>
            <div
              v-if="index !== headers.length - 1"
              class="pm-table-column-resizer"
              @mousedown="startResize(index)"
            >
            </div>
          </th>
        </tr>
        <tr>
          <th class="pm-table-border" :colspan="headers.length"></th>
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

import moment from "moment";

export default {
  components: {
  },
  props: {
    headers: {
      default: [
        {
          label: "#",
          field: "id",
          sortable: true,
          width: 45,
        },
      ],
      type: [
        {
          label: String,
          field: String,
          sortable: Boolean,
          width: Number,
        },
      ],
    },
    data: {
      default: [
        {
          id: 24,
        },
      ],
      type: [
        {
          id: Number,
        },
      ],
    },
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
      this.headers.forEach((column) => {
        if (column.format) {
          if (column.format === 'datetime' || column.format === 'date') {
            this.data.data.forEach((element) => {
              element[column.field] = this.formatDate(element[column.field], column.format);
            });
          }
        }
      });
    },
  },
  mounted() {
    this.$nextTick(() => {
      const ellipsisColumn = document.querySelectorAll('.pm-table-ellipsis-column');

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
      const dateTimeFormat = "MM/DD/YY HH:mm";
      const dateFormat = "MM/DD/YY";
      if (mask === 'datetime') {
        return date === null ? "-" : moment(date).format(dateTimeFormat);
      }
      if (mask === 'date') {
        return date === null ? "-" : moment(date).format(dateFormat);
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
.pm-table-container {
  overflow-x: auto;
  max-height: 400px;
  overflow-y: auto;
}

.pm-table-container th {
  position: relative;
  padding: 8px;
}

.pm-table-column-header {
  overflow: hidden;
  white-space: nowrap;
}

.pm-table-column-resizer {
  position: absolute;
  right: -5px;
  top: 50%;
  transform: translateY(-50%);
  height: 30px;
  width: 10px;
  cursor: col-resize;
  border-left: 1px solid rgba(0, 0, 0, 0.125);
}
.pm-table-filter {
  width: 100%;
  max-height: 400px;
  border-collapse: collapse;
  border-left: 1px solid rgba(0, 0, 0, 0.125);
  border-right: 1px solid rgba(0, 0, 0, 0.125);
  position: relative;
}
.pm-table-filter td {
  border-top: 1px solid rgba(0, 0, 0, 0.125);
  border-bottom: 1px solid rgba(0, 0, 0, 0.125);
  padding: 10px 16px;
}
.pm-table-ellipsis-column {
  padding: 10px 16px;
}
.pm-table-filter th:hover {
  background-color: #FAFBFC;
  color: #1572C2;
}
.pm-table-filter tbody tr:hover {
  background-color: #FAFBFC;
  color: #1572C2;
}
.pm-table-filter thead {
  position: sticky;
  top: 0;
  background-color: #fff;
}
.pm-table-filter .sortable-column:hover::after {
  content: '\2026';
  position: absolute;
  top: 50%;
  right: 7px;
  transform: translateY(-50%) rotate(90deg);
  font-size: 16px;
  line-height: 1;
  cursor: pointer;
}
.pm-table-border {
  height: 1px;
  padding: 0 !important;
  background-color: rgba(0, 0, 0, 0.125);
  border: 0 !important;
}
.pm-table-filter-button {
  position: absolute;
  top: 10%;
  right: 7px;
}
.pm-table-ellipsis-column .pm-table-filter-button {
  display: none;
}
.pm-table-ellipsis-column:hover .pm-table-filter-button {
  display: block;
}

</style>
