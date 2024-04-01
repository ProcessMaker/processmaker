<template>
  <div class="pl-3">

          <div v-if="propFromButton === 'fullTask'" class="header-container">
            <span class="quick-fill-text-full">{{ $t("Quick Fill") }}</span>
            <b-button
            class="close-go-back-button"
            @click="cancelAndGoBack()"
          >
          {{ $t('Cancel And Go Back') }}
          </b-button>
          </div>
          <div v-else>
            <div class="button-container">
          <span class="quick-fill-text">{{ $t("Quick Fill") }}</span>
            <b-button
              class="close-button-prev button-cancel"
              @click="$emit('close')"
            >
            {{ $t("Cancel") }}
            </b-button>
          </div>
          </div>

    <div class="second-container">
      <div class="span-message">
        {{ this.processName 
        ? $t('Select a previous task to reuse its filled data on the current task') + ': ' + this.processName 
        : $t('Select a previous task to reuse its filled data on the current task.') }}
      </div>
      <div class="third-container">
        <tasks-list
          ref="taskList"
          class="custom-table-class"
          :disable-tooltip="true"
          :columns="columns"
          :selected-row-quick="selectedRowQuick"
          @selected="selected"
          :pmql="pmql"
          :advanced-filter-prop="quickFilter"
          :from-button="propFromButton"
        >
          <template v-slot:preview-header="{ close, screenFilteredTaskData }">
            <div style="width: 92%;">
              <div class="header-container-quick">
                <div style="display: block; width: 100%;">
                <span class="span-text">Data Preview</span>
                <b-button
                  v-if="propFromButton !== 'fullTask'"
                    class="button-task mr-2"
                    variant="primary"
                    :aria-label="$t('Use This Task Data')"
                    @click="buttonThisData(screenFilteredTaskData)"
                  >
                  <img
                    src="../../../img/smartinbox-images/Stroke.svg"
                    class="img-styles"
                    :alt="$t('No Image')"
                  />{{ $t('Use This Task Data') }}
                  </b-button>
                  <b-button
                  v-if="propFromButton === 'fullTask'"
                    class="button-task mr-2"
                    variant="primary"
                    :aria-label="$t('Use This Task Data')"
                    @click="buttonThisDataFromFullTask(screenFilteredTaskData)"
                  >
                  <img
                    src="../../../img/smartinbox-images/Stroke.svg"
                    class="img-styles"
                    :alt="$t('No Image')"
                  />{{ $t('Use This Task Data') }}
                  </b-button>
                  
              </div>
              <b-button
                    class="close-button mr-2"
                    variant="link"
                    @click="close()"
                  >
                    <i class="fas fa-times" />
                  </b-button>
              </div>
            </div>
          </template>
          <template v-slot:tooltip="{ tooltipRowData, previewTasks }">
            <b-button
              v-if="propFromButton === 'previewTask'"
              class="icon-button"
              :aria-label="$t('Quick fill Preview')"
              variant="light"
              @click="previewTasks(tooltipRowData, 93, 'previewTask')"
            >
              <i class="fas fa-eye"/>
            </b-button>
            <b-button
              v-if="propFromButton === 'fullTask'"
              class="icon-button"
              :aria-label="$t('Quick fill Preview')"
              variant="light"
              @click="previewTasks(tooltipRowData, 50, 'fullTask'); setTask()"
            >
              <i class="fas fa-eye"/>
            </b-button>
            <b-button
              v-if="propFromButton === 'inboxRules'"
              class="icon-button"
              :aria-label="$t('Quick fill Preview')"
              variant="light"
              @click="previewTasks(tooltipRowData, 50, 'inboxRules');"
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
  props: ["task", "propColumns", "propFilters", "propFromButton"],
  data() {
    return {
      processName: "",
      selectedRowQuick: 0,
      fromQuickFill: true,
      taskData: {},
      pmql: `(user_id = ${ProcessMaker.user.id} and status="Completed" and process_id=${this.task.process_id})`,
      quickFilter: null,
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
  mounted() {
    if(this.propFilters !== "") {
      this.quickFilter = this.propFilters;
    }

    if(this.propColumns.length > 0) {
      this.columns = this.propColumns;
    }
  },
  methods: {
    verifyURL(string) {
      const currentUrl = window.location.href;
      const isInUrl = currentUrl.includes(string);
      return isInUrl;
    },
    selected(taskData) {},
    setTask() {
      this.processName = this.task.process_request.case_title;
    },
    cancelAndGoBack() {
      window.location.href = `/tasks/${this.task.id}/edit`;
    },
    buttonThisData(data) {
      if(this.propFromButton === 'inboxRules'){
        this.$emit("quick-fill-data-inbox", data);
      } else {
        this.$emit("quick-fill-data", data);
      }
      this.$emit("close");
    },
    buttonThisDataFromFullTask(data) {
      return ProcessMaker.apiClient
        .put("drafts/" + this.task.id, data)
        .then((response) => {
          this.task.draft = _.merge(
            {},
            this.task.draft,
            response.data
          );
          window.location.href = `/tasks/${this.task.id}/edit`;
          ProcessMaker.alert(this.$t('Task Filled successfully.'), 'success');
        })
        .catch((error) => {
          console.error("Error", error);
        })
    },
  },
};
</script>
<style scoped>

.btn-cancel {
  background-color: #fff;
}
.btn-back-quick-fill {
  color: #888;
  padding: 0;
  border: none;
}
.button-container {
  display: flex;
  justify-content: space-between;
  height: 64px;
  border: 1px solid #f6f9fb;
  padding: 0 12px;
}

.header-container {
  display: flex;
  align-items: center;
  border: 1px solid #f6f9fb;
  padding: 0 12px;
}

.header-container-quick {
  display: flex;
  justify-content: space-between;
  border: 1px solid #CDDDEE;
  padding: 10px 12px;
  background-color: #E8F0F9;
}

.close-go-back-button {
  color: #fff;
  background-color: #6A7888;
  width: 228px;
  height: 40px;
  border-radius: 4px;
  padding: 0;
  border: none;
  margin-left: auto;
}
.quick-fill-text-full {
  color: #556271;
  font-size: 27px;
}
.quick-fill-text {
  color: #566877;
  margin-left: 8px;
  font-size: 16px;
}

.button-task {
  color: #fff;
  background-color: #1572C2;
  display: block; 
  width: 100%; 
  margin-top:10px;
}

.button-cancel {
  color: #fff;
  background-color: #6A7888;
  width: 88px;
  height: 32px;
  font-weight: bold;
}

.close-button-prev {
  color: #fff;
  padding: 0;
  border: none;
}

.arrow-button,
.close-button-prev {
  color: #fff;
  padding: 0;
  border: none;
}

.close-button {
  color: #888;
  padding: 0;
  border: none;
  margin-left: -25px;
  margin-top: -45px;
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
  margin-left: 10px;
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

.img-styles {
  margin-right: 5px; 
  margin-top: -2px;
}

.icon-button {
  color: #888;
}

.span-text {
  font-size: 16px;
  color: #556271;
  font-weight: bold;
}
</style>
