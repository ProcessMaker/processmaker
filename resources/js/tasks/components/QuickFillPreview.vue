<template>
  <div class="pl-3">
    <div
      v-if="propFromButton === 'fullTask'"
      class="header-container"
    >
      <span class="quick-fill-text-full">{{ $t("Quick Fill") }}</span>
      <b-button
        class="close-go-back-button"
        @click="cancelAndGoBack()"
      >
        {{ $t("Cancel And Go Back") }}
      </b-button>
    </div>
    <div
      v-if="propFromButton === 'inboxRules'"
      class="button-container"
    >
      <span class="quick-fill-text">{{ $t("Quick Fill") }}</span>
      <b-button
          class="close-button-prev button-cancel"
          @click="$emit('close')"
        >
          {{ $t("Cancel") }}
        </b-button>
    </div>

      <div v-if="propFromButton === 'previewTask'" 
        class="button-container">
        
        <b-button
          class="close-button-prev button-cancel"
          @click="$emit('close')"
        >
          {{ $t("Cancel") }}
        </b-button>
      </div>


    <div class="second-container">
      <div class="span-message">
        {{ this.processName ? 
          $t("Select a previous task to reuse its filled data on the current task") + ": " + this.processName : 
          $t("Select a previous task to reuse its filled data on the current task.")
        }}
      </div>
      <div class="third-container">
        <tasks-list
          ref="taskList"
          class="custom-table-class"
          :columns="columns"
          :fetch-on-created="false"
          :selected-row-quick="selectedRowQuick"
          :table-name="tasksListName"
          @selected="selected"
          :pmql="pmql"
          :advanced-filter-prop="quickFilter"
          :from-button="propFromButton"
          @onWatchShowPreview="onWatchShowPreview"
        >
          <template v-slot:preview-header="{ close, screenFilteredTaskData, taskReady }">
            <div v-if="propFromButton === 'inboxRules'" style="width: 98%">
              <div class="header-container-quick">
                <div style="display: block; width: 100%">
                  <span class="span-text">Data Preview</span>
                  <b-button
                    v-if="propFromButton === 'inboxRules'"
                    class="button-task mr-2"
                    variant="primary"
                    :disabled="!taskReady"
                    :aria-label="$t('Use This Task Data')"
                    @click="buttonThisData(screenFilteredTaskData)"
                  >
                    <img
                      src="../../../img/smartinbox-images/Stroke.svg"
                      class="img-styles"
                      :alt="$t('No Image')"
                    />{{ $t("Use This Task Data") }}
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
              <div class="header-container-warning">
                <p>{{ disclaimer }}</p>
              </div>
            </div>
            <div v-else style="width: 92%">
              <div class="header-container-quick">
                <div style="display: block; width: 100%">
                  <span class="span-text">Data Preview</span>
                  <b-button
                    v-if="propFromButton === 'previewTask'"
                    class="button-task mr-2"
                    :disabled="!taskReady"
                    variant="primary"
                    :aria-label="$t('Use This Task Data')"
                    @click="buttonThisData(screenFilteredTaskData)"
                  >
                    <img
                      src="../../../img/smartinbox-images/Stroke.svg"
                      class="img-styles"
                      :alt="$t('No Image')"
                    />{{ $t("Use This Task Data") }}
                  </b-button>
                  <b-button
                    v-if="propFromButton === 'fullTask'"
                    class="button-task mr-2"
                    :disabled="!taskReady"
                    variant="primary"
                    :aria-label="$t('Use This Task Data')"
                    @click="buttonThisDataFromFullTask(screenFilteredTaskData)"
                  >
                    <img
                      src="../../../img/smartinbox-images/Stroke.svg"
                      class="img-styles"
                      :alt="$t('No Image')"
                    />{{ $t("Use This Task Data") }}
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
              <div class="header-container-warning">
                <p>{{ disclaimer }}</p>
              </div>
            </div>
          </template>
          <template v-slot:tooltip="{ tooltipRowData, previewTasks }">
            <b-button
              v-if="propFromButton === 'previewTask'"
              :aria-label="$t('Quick fill Preview')"
              variant="light"
              size="sm"
              @click="previewTasks(tooltipRowData, 93, 'previewTask')"
            >
              <i class="fas fa-eye" />
            </b-button>
            <b-button
              v-if="propFromButton === 'fullTask'"
              :aria-label="$t('Quick fill Preview')"
              variant="light"
              size="sm"
              @click="
                previewTasks(tooltipRowData, 50, 'fullTask');
                setTask();
              "
            >
              <i class="fas fa-eye" />
            </b-button>
            <b-button
              v-if="propFromButton === 'inboxRules'"
              :aria-label="$t('Quick fill Preview')"
              variant="light"
              size="sm"
              @click="previewTasks(tooltipRowData, 50, 'inboxRules')"
            >
              <i class="fas fa-eye" />
            </b-button>
          </template>
        </tasks-list>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: ["task", "propColumns", "propFilters", "propFromButton", "screenFields"],
  data() {
    return {
      processName: "",
      selectedRowQuick: 0,
      fromQuickFill: true,
      taskData: {},
      pmql: `(user_id = ${ProcessMaker.user.id} and status="Completed" and process_id=${this.task.process_id})`,
      quickFilter: null,
      filter: {
        order: { by: "id", direction: "desc" },
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
          width: 100,
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
          width: 180,
        },
        {
          label: "Completed",
          field: "completed_at",
          format: "datetime",
          filter_subject: {
            type: "Field",
            value: "completed_at",
          },
          width: 140,
        },
      ],
      dataTasks: {},
      disclaimer: this.$t("This is a Beta version and when using Quickfill, it may replace the pre-filled information in the form."),
      tasksListName: "preview-table",
    };
  },
  mounted() {
    if (this.propFilters !== "" && !this.propFilters.filters.some(filter => filter.value === null)) {
      this.quickFilter = this.propFilters;
    } else {
      this.quickFilter = this.filter;
    }

    if (this.propColumns.length > 0) {
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
      if (this.propFromButton === "inboxRules") {
        this.$emit("quick-fill-data-inbox", data);
      } else {
        this.$emit("quick-fill-data", data);
      }
      this.$emit("close");
    },
    validateBase64(field) {
      const regex = /^data:image\/\w+;base64,/;
      return regex.test(field);
    },
    buttonThisDataFromFullTask(quickFillData) {
      // If the task does not have a draft yet, use the task data
      const dataToUse = this.task.draft?.data ?? this.task.data;

      const draftData = {};
      this.screenFields.forEach((field) => {
        const existingValue = _.get(dataToUse, field, null);

        let quickFillValue;
        if (existingValue) {
          // If the value exists in the task data (or task draft data), don't overwrite it
          quickFillValue = existingValue;
        } else {
          // use the value from the quick fill
          quickFillValue = _.get(quickFillData, field, null);
        }

        if(this.validateBase64(quickFillValue)) {
          _.set(draftData, field, existingValue);
          return;
        }
        // Set the value. This handles nested values using dot notation in 'field' string
        _.set(draftData, field, quickFillValue);
      });

      return ProcessMaker.apiClient
        .put("drafts/" + this.task.id, draftData)
        .then((response) => {
          this.task.draft = _.merge({}, this.task.draft, response.data);
          window.location.href = `/tasks/${this.task.id}/edit`;
          ProcessMaker.alert(this.$t("Task Filled successfully."), "success");
        })
        .catch((error) => {
          console.error("Error", error);
        });
    },
    /*
     * To do: There's a global-search-bar class with a large z-index. We added a class 
     * that modifies this class when this panel is displayed. If it's destroyed, we 
     * remove it to maintain compatibility.
     */
    reCalculateZIndex(sw) {
      let names = [".global-search-bar", ".navbar-nav"];
      for (let value of names) {
        let searchBar = document.querySelector(value);
        if (searchBar) {
          let obj = searchBar.classList;
          if (sw === true) {
            obj.add("global-search-bar-z-index");
          } else {
            obj.remove("global-search-bar-z-index");
          }
        }
      }
    },
    onWatchShowPreview(value) {
      this.reCalculateZIndex(value);
    }
  },
};
</script>
<style>
  /*To do: Read description of method reCalculateZIndex()*/
  .global-search-bar-z-index {
    z-index: auto !important;
  }
</style>
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
  border: 1px solid #cdddee;
  padding: 10px 12px;
  background-color: #e8f0f9;
}

.header-container-warning {
  display: flex;
  justify-content: space-between;
  border: 1px solid #F1E4BA;
  padding: 10px 12px;
  background-color: #FEF7E2;
  color: #556271;
  font-size: 16px;
}

.close-go-back-button {
  color: #fff;
  background-color: #6a7888;
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
  background-color: #1572c2;
  display: block;
  width: 100%;
  margin-top: 10px;
}

.button-cancel {
  color: #fff;
  background-color: #6a7888;
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

.second-container {
  width: 99%;
}

.third-container {
  width: 99%;
}
</style>
