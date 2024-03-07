<template>
  <div>
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
        <div class="suggested-task">
          <img
            src="../../../img/smartinbox-images/fill.svg"
            :alt="$t('No Image')"
          />
          <div class="text-container">
            <span class="main-text">Quick fill task with:</span>
            <span class="sub-text">Credit requested by Matthew Harris for  $US 4.900  |  Process: Client Request  |  Task: Loan Cre...</span>
          </div>
          
        </div>
        <tasks-list
          ref="taskList"
          class="custom-table-class"
          :disable-tooltip="true"
          :columns="columns"
          @selected="selected"
          :pmql="pmql"
          :advanced-filter-prop="filter"
        >
          <template v-slot:tooltip="{ tooltipRowData }">
            <b-button
              class="icon-button"
              :aria-label="$t('Quick fill')"
              variant="light"
              @click="buttonThisData(tooltipRowData)"
            >
              <img
                src="../../../img/smartinbox-images/Vector.svg"
                :alt="$t('No Image')"
              />
            </b-button>
            <b-button
              class="icon-button"
              :aria-label="$t('Quick fill Preview')"
              variant="light"
              @click="buttonPreviewThisData(tooltipRowData)"
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
      filter: [
        {
          subject: { type: "Status" },
          operator: "=",
          value: "Completed",
          _column_field: "status",
          _column_label: "Status",
        },
      ],
      pmql:
        '(user_id = 1 and status="Completed" and process_id=' +
        this.task.process_id +
        ")",
      columns: [
        {
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 60,
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
          sortable: true,
          default: true,
          width: 150,
          truncate: true,
          filter_subject: {
            type: "Relationship",
            value: "processRequest.case_title",
          },
          order_column: "process_requests.case_title",
        },
        {
          label: "Due date",
          field: "due_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160,
        },
      ],
      dataTasks: {},
    };
  },
  methods: {
    selected(taskData) {},
    buttonThisData(tooltipRowData) {
      this.$emit("quick-fill-data", tooltipRowData.data);
      this.$emit("close");
    },
    buttonPreviewThisData(tooltipRowData) {
      this.$emit("quick-fill-data-preview", tooltipRowData.data);
      this.$emit("close");
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
  border: 1px solid #fef7e2;
  margin: 20px 0;
  padding: 10px 20px;
  background-color: #f1e4ba;
  height: 64px;
}

.suggested-task img {
  margin-right: 5px; 
}

.suggested-task span {
  color: #556271;
}
.main-container,
.button-container {
  border: 1px solid #f6f9fb;
}

.main-container {
  display: flex;
  flex-direction: column;
  margin: 0 -16px;
}

.button-container {
  padding: 0 -1px;
  height: 64px;
}

.second-container {
  background-color: #f6f9fb;
  border-top: 1px solid #f6f9fb;
  margin-left: -16px;
  margin-right: -16px;
}

.third-container {
  border-top: 1px solid #f6f9fb;
  margin-left: 15px;
  margin-right: 15px;
}
.content-container {
  margin: 20px;
}

.quick-fill-text {
  margin-left: 8px;
}

.custom-table-class {
  background-color: #fff;
}

.suggested-task {
  border: 1px solid #f1e4ba;
  padding: 10px;
  background-color: #fef7e2;
  height: 64px;
}

.suggested-task span {
  color: #556271;
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
