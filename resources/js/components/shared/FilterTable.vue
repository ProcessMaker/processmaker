<template>
  <div
    id="table-container"
    class="pm-table-container"
  >
    <table
      class="pm-table-filter"
      aria-label="custom-pm-table"
      @mouseleave="handleRowMouseleave"
    >
      <thead>
        <tr>
          <th class="pm-table-border" :colspan="headers.length"></th>
        </tr>
        <tr>
          <th
            class="pm-table-ellipsis-column"
            v-for="(column, index) in headers"
            :key="index"
            :id="`column-${index}`"
            :class="{ 'pm-table-filter-applied': column.filterApplied }"
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
          :id="`row-${row.id}`"
          :class="{ 'pm-table-unread-row': isUnread(row, unread) }"
          @click="handleRowClick(row)"
          @mouseover="handleRowMouseover(row)"
        >
          <slot :name="`row-${rowIndex}`">
            <td
              v-for="(header, index) in headers"
              :key="index"
            >
              <template v-if="containsHTML(getNestedPropertyValue(row, header.field))">
                <div
                  :id="`element-${rowIndex}-${index}`"
                  :class="{ 'pm-table-truncate': header.truncate }"
                  :style="{ maxWidth: header.width + 'px' }"
                >
                  <div v-html="sanitize(getNestedPropertyValue(row, header.field))"></div>
                </div>
                <b-tooltip
                  v-if="header.truncate"
                  :target="`element-${rowIndex}-${index}`"
                  custom-class="pm-table-tooltip"
                >
                  {{ sanitizeTooltip(getNestedPropertyValue(row, header.field)) }}
                </b-tooltip>
              </template>
              <template v-else>
                <template v-if="isComponent(row[header.field])">
                  <component
                    :is="row[header.field].component"
                    v-bind="row[header.field].props"
                  >
                  </component>
                </template>
                <template v-else>
                  <div
                    :id="`element-${rowIndex}-${index}`"
                    :class="{ 'pm-table-truncate': header.truncate }"
                    :style="{ maxWidth: header.width + 'px' }"
                  >
                    {{ getNestedPropertyValue(row, header.field) }}
                    <b-tooltip
                      v-if="header.truncate"
                      :target="`element-${rowIndex}-${index}`"
                      custom-class="pm-table-tooltip"
                    >
                      {{ getNestedPropertyValue(row, header.field) }}
                    </b-tooltip>
                  </div>
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
import FilterTableBodyMixin from "./FilterTableBodyMixin";

export default {
  components: {
  },
  mixins: [FilterTableBodyMixin],
  props: {
    headers: {
      type: Array,
      default: function () {
        return [];
      }
    },
    data: [],
    unread: {
      type: String,
      default: function () {
        return "";
      }
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
            if (this.data?.data?.forEach) {
              this.data.data.forEach((element) => {
                element[column.field] = this.formatDate(element[column.field], column.format);
              });
            }
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
    handleRowClick(row) {
      this.$emit('table-row-click', row);
    },
    handleRowMouseover(row) {
      this.$emit('table-row-mouseover', row);
    },
    handleRowMouseleave() {
      this.$emit('table-row-mouseleave', false);
    },
    sanitizeTooltip(html) {
      let cleanHtml = html.replace(/<script(.*?)>[\s\S]*?<\/script>/gi, "");
      cleanHtml = cleanHtml.replace(/<style(.*?)>[\s\S]*?<\/style>/gi, "");
      cleanHtml = cleanHtml.replace(/<(?!img|input|meta|time|button|select|textarea|datalist|progress|meter)[^>]*>/gi, "");
      cleanHtml = cleanHtml.replace(/\s+/g, " ");

      return cleanHtml;
    },
    isUnread(row, unreadColumnName) {
      return row[unreadColumnName] === null;
    },
  },
};
</script>

<style>
.pm-table-container {
  overflow-x: auto;
  overflow-y: auto;
  border-left: 1px solid rgba(0, 0, 0, 0.125);
  border-right: 1px solid rgba(0, 0, 0, 0.125);
  border-bottom: 1px solid rgba(0, 0, 0, 0.125);
  border-radius: 5px;
  scrollbar-width: 8px;
  scrollbar-color: #6C757D;
}

.pm-table-container th {
  position: relative;
  max-width: 800px;
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
  height: 85%;
  width: 10px;
  cursor: col-resize;
  border-left: 1px solid rgba(0, 0, 0, 0.125);
}
.pm-table-filter {
  width: 100%;
  max-height: 400px;
  border-collapse: collapse;
  position: relative;
  color: #566877;
}
.pm-table-filter td {
  border-bottom: 1px solid rgba(0, 0, 0, 0.125);
  padding: 10px 16px;
  height: 56px;
}
.pm-table-ellipsis-column {
  padding: 10px 16px;
  height: 56px;
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
  top: 20%;
  right: 7px;
}
.pm-table-ellipsis-column .pm-table-filter-button {
  opacity: 0;
  visibility: hidden;
}
.pm-table-ellipsis-column:hover .pm-table-filter-button {
  opacity: 1;
  visibility: visible;
}
.pm-table-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.pm-table-tooltip {
  opacity: 1 !important;
}
.pm-table-tooltip .tooltip-inner {
  background-color: #F2F8FE;
  color: #6A7888;
  box-shadow: -5px 5px 5px rgba(0, 0, 0, 0.3);
  max-width: 250px;
  padding: 14px;
  border-radius: 7px;
}
.pm-table-tooltip .arrow::before {
  border-bottom-color: #F2F8FE !important;
  border-top-color: #F2F8FE !important;
}
.pm-table-filter-applied {
  color: #1572C2;
  background-color: #F2F8FE !important;
}
.pm-table-unread-row {
  font-weight: bold;
}
.status-success {
  background-color: rgba(78, 160, 117, 0.2);
  color: rgba(78, 160, 117, 1);
  width: 100px;
  border-radius: 5px;
}
.status-danger {
  background-color:rgba(237, 72, 88, 0.2);
  color: rgba(237, 72, 88, 1);
  width: 100px;
  border-radius: 5px;
}
.status-primary {
  background: rgba(21, 114, 194, 0.2);
  color: rgba(21, 114, 194, 1);
  width: 100px;
  border-radius: 5px;
}
@-moz-document url-prefix() {
  .pm-table-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}
.pm-table-container::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
.pm-table-container::-webkit-scrollbar-thumb {
  background-color: #6C757D;
  border-radius: 20px;
}
</style>
