<template>
  <div class="pl-3">
    <div class="main-container">
      <div class="button-container">
        <b-button
          class="btn-back-quick-fill"
          variant="link"
          @click="$emit('close')"
        >
          <i class="fas fa-arrow-left" />
        </b-button>
        <span class="quick-fill-text">{{ $t("Quick Fill") }}</span>
        <b-button
          class="close-button"
          variant="link"
          @click="$emit('close')"
        >
          <i class="fas fa-times" />
        </b-button>
      </div>
    </div>
    <div class="second-container">
      <div class="span-message">
        <span>Select a previous task to reuse its filled data on the current task.</span>
      </div>
      <div class="third-container">
        <tasks-list
          ref="taskList"
          class="custom-table-class"
          :columns="columns"
          @selected="selected"
          :pmql="pmql"
          :advanced-filter-prop="filter"
          :disable-row-click="true"
        >
          <template v-slot:preview-header="{ close, screenFilteredTaskData }">
            <div>
              <b-button
                  class="mr-2"
                  variant="primary"
                  :aria-label="$t('Use This Task Data')"
                  @click="buttonThisData(screenFilteredTaskData)"
                >
                  {{ $t('Use This Task Data') }}
                </b-button>
                <b-button
                  class="close-button mr-2"
                  variant="link"
                  @click="close()"
                >
                  <i class="fas fa-times" />
                </b-button>
            </div>
          </template>
          <template v-slot:tooltip="{ tooltipRowData, previewTasks }">
            <b-button
              class="icon-button"
              :aria-label="$t('Quick fill Preview')"
              variant="light"
              @click="previewTasks(tooltipRowData, 93)"
            >
              <i class="fas fa-eye"/>
            </b-button>
          </template>
        </tasks-list>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: ["task", "data"],
  data() {
    return {
      taskData: {},
      processID: 27,
      filter: {
        order: { by: 'id', direction: 'desc' },
        filters: [
          {
            subject: { type: "Field", value: "process_id" },
            operator: "=",
            value: this.task.process_id,
          },
          {
            subject: { type: "Field", value: "element_id" },
            operator: "=",
            value: this.task.element_id,
          },
        ],
      },
      pmql: '(user_id = 1 and status="Completed")',
      columns: [
        {
          label: "Case #",
          field: "case_number",
          filter_subject: {
            type: "Relationship",
            value: "processRequest.case_number",
          },
          order_column: "process_requests.case_number",
        },
        {
          label: "Case title",
          field: "case_title",
          name: "__slot:case_number",
          filter_subject: {
            type: "Relationship",
            value: "processRequest.case_title",
          },
          order_column: "process_requests.case_title",
        },
        {
          label: "Completed",
          field: "completed_at",
          format: "datetime",
          filter_subject: {
            type: "Field",
            value: "completed_at",
          },
        }
      ],
      dataTasks: {},
    };
  },
  methods: {
    selected(taskData) {},
    buttonThisData(data) {
      this.$emit("quick-fill-data", data);
      this.$emit("close");
    },
    buttonPreviewThisData(tooltipRowData) {
    },
  },
};
</script>
<style scoped>

.btn-cancel {
  background-color: #d8e0e9;
}
.btn-back-quick-fill {
  color: #888;
  padding: 0;
  border: none;
}
.button-container {
  display: flex;
  align-items: center;
  height: 64px;
  border: 1px solid #f6f9fb;
  padding: 0 12px;
}
.quick-fill-text {
  color: #888;
  margin-left: 8px;
}
.close-button {
  color: #888;
  padding: 0;
  border: none;
  margin-left: auto;
}
.arrow-button,
.close-button {
  color: #888;
  padding: 0;
  border: none;
}
.suggested-task {
  display: flex;
  align-items: center;
  border: 1px solid #f1e4ba;
  margin: 20px 0;
  padding: 10px 20px;
  background-color: #fef7e2;
  height: 64px;
}

.suggested-task img {
  margin-right: 5px; 
}

.suggested-task span {
  color: #556271;
}
.content-container {
  margin: 20px;
}

.custom-table-class {
  background-color: #fff;
}

.text-container {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-left: 10px; /* Ajusta el margen izquierdo según sea necesario */
}

.main-text {
  color: #556271;
  font-size: 16px;
}

.sub-text {
  color: #556271;
  font-size: 14px;
  margin-left: -30px;
}

.span-message {
  background-color: #f6f9fb;
  font-size: 16px;
  color: #556271;
  padding: 10px;
  margin-top: 5px;
}

img {
  align-self: flex-start;
  margin-right: 2px;
  margin-top: 3px;
}

.icon-button {
  color: #888;
}
</style>
