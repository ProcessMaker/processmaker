<template>
  <div>
    <modal
      id="launchpadSettingsModal"
      ref="my-modal-save"
      size="lg"
      class="modal-dialog modal-dialog-centered"
      :title="$t('Launchpad Settings')"
      :set-custom-buttons="true"
      :custom-buttons="customModalButtons"
      @saveModal="saveModal"
      @closeModal="closeModal"
    >
      <div class="modal-content-custom">
        <p class="text-info-custom">
          {{
            $t(
              "Here you can personalize how your process will be shown in the process browser"
            )
          }}
        </p>
        <div class="row">
          <div class="col-sm-12 col-lg-6">
            <div
              md="12"
              class="no-padding"
            >
              <label>{{ $t("Launchpad Carousel") }}</label>
              <input-image-carousel ref="image-carousel" />
            </div>
          </div>
          <div class="col-sm-12 col-lg-6 options-launchpad">
            <label>{{ $t("Launch Screen") }}</label>
            <div class="multiselect-screen custom-multiselect">
              <b-input-group>
                <multiselect
                  v-model="selectedScreen"
                  :placeholder="$t('Type to search Screen')"
                  :options="dropdownSavedScreen"
                  :multiple="false"
                  track-by="id"
                  label="title"
                  :show-labels="false"
                  :searchable="true"
                  :internal-search="true"
                  :allow-empty="false"
                  @open="retrieveDisplayScreen"
                  @search-change="retrieveDisplayScreen"
                  @input="changeSelectedScreen"
                >
                  <template slot="noResult">
                    {{
                      $t(
                        "No elements found. Consider changing the search query."
                      )
                    }}
                  </template>
                  <template slot="noOptions">
                    {{ $t("No Data Available") }}
                  </template>
                </multiselect>
              </b-input-group>
            </div>
            <label>
              {{ $t("Launchpad Icon") }}
            </label>
            <icon-dropdown ref="icon-dropdown" />
            <label>{{ $t("Chart") }}</label>
            <div class="multiselect-chart custom-multiselect">
              <b-input-group>
                <multiselect
                  v-model="selectedSavedChart"
                  :placeholder="$t('Type to search Chart')"
                  :options="dropdownSavedCharts"
                  :multiple="false"
                  track-by="id"
                  label="title"
                  :show-labels="false"
                  :searchable="true"
                  :internal-search="true"
                  :allow-empty="false"
                  @open="retrieveSavedSearchCharts"
                  @search-change="retrieveSavedSearchCharts"
                >
                  <template slot="noResult">
                    {{
                      $t(
                        "No elements found. Consider changing the search query."
                      )
                    }}
                  </template>
                  <template slot="noOptions">
                    {{ $t("No Data Available") }}
                  </template>
                </multiselect>
              </b-input-group>
            </div>
            <label />
            <div v-if="showTasks">
              <a
                href="#"
                @click.prevent="showEditColumn('tasks')"
              >
                {{ $t("Edit Task Column") }}
                <i class="fp-box-arrow-up-right" />
              </a>
            </div>
            <div v-if="showCases">
              <a
                href="#"
                @click.prevent="showEditColumn('cases')"
              >
                {{ $t("Edit Cases Column") }}
                <i class="fp-box-arrow-up-right" />
              </a>
            </div>
          </div>
        </div>
        <label>
          {{ $t("Description") }}
        </label>
        <textarea
          id="additional-details"
          v-model="processDescription"
          class="form-control input-custom mb-0"
          type="text"
          rows="5"
          :aria-label="$t('Description')"
          disabled
        />
      </div>
      <template #modal-footer>
        <b-button
          variant="outline-secondary"
          @click="hideModal"
        >
          Cancel 2
        </b-button>
        <b-button
          variant="secondary"
          @click="saveModal"
        >
          Save 1
        </b-button>
      </template>
    </modal>
    <edit-column-modal
      ref="editColumnModal"
      :data-columns="columnListing"
      :type="typeListing"
      @updateColumns="updateColumns"
    />
  </div>
</template>

<script>
import Modal from "./Modal.vue";
import IconDropdown from "./IconDropdown.vue";
import InputImageCarousel from "./InputImageCarousel.vue";
import EditColumnModal from "./EditColumnModal.vue";

const isTceCustomization = () => window.ProcessMaker?.isTceCustomization;

export default {
  components: {
    Modal, IconDropdown, InputImageCarousel, EditColumnModal,
  },
  props: {
    options: {
      type: Object,
      default: () => ({
        id: "",
        type: "",
      }),
    },
    filter: {
      type: String,
      default: "",
    },
    origin: {
      type: String,
      default: "",
    },
    descriptionSettings: {
      type: String,
      default: "",
    },
    process: {
      type: Object,
      default: () => ({}),
    },
    myTasksColumns: {
      type: Array,
      default: () => [],
    },
    myCasesColumns: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      imagesMedia: [],
      list: {},
      subject: "",
      description: "",
      errors: "",
      selectedSavedChart: null,
      tceScreens: [
        {
          id: "tce-student",
          uuid: "",
          title: this.$t("Distribution Bar Fin Aid Student"),
        },
        {
          id: "tce-college",
          uuid: "",
          title: this.$t("Distribution Bar Fin Aid College"),
        },
        {
          id: "tce-grants",
          uuid: "",
          title: this.$t("Distribution Bar Grants"),
        },
      ],
      defaultScreen: {
        id: 0,
        uuid: "",
        title: this.$t("Default Launchpad"),
      },
      defaultChart: {
        id: 0,
        title: this.$t("Default Launchpad Chart"),
      },
      defaultIcon: "Default Icon",
      dropdownSavedCharts: [],
      dropdownSavedScreen: [],
      processDescription: "",
      processDescriptionInitial: "",
      selectedLaunchpadIcon: "",
      selectedLaunchpadIconLabel: "",
      showVersionInfo: true,
      isSecondaryColor: false,
      selectedScreen: null,
      processId: "",
      mediaImageId: [],
      dataProcess: {},
      oldScreen: 0,
      customModalButtons: [
        {
          content: "Cancel",
          action: "closeModal",
          variant: "outline-secondary",
          dataTest: "launchpad-modal-btn-cancel",
          disabled: false,
        },
        {
          content: "Save",
          action: "saveModal",
          variant: "secondary",
          dataTest: "launchpad-modal-btn-ok",
          disabled: false,
        },
      ],
      tabs: [],
      columnListing: {},
      typeListing: "",
      myTasks: {
        currentColumns: [],
        availableColumns: [],
        defaultColumns: [],
        dataColumns: [],
      },
      myCases: {
        currentColumns: [],
        availableColumns: [],
        defaultColumns: [],
        dataColumns: [],
      },
      ScreenDefaultId: [0, "tce-student", "tce-college", "tce-grants"],
    };
  },
  computed: {
    isEditColumns() {
      if (this.ScreenDefaultId.includes(this.selectedScreen?.id)) {
        return true;
      }
      return false;
    },
    isTCEScreen() {
      if (this.selectedScreen?.id === 0) {
        return false;
      }
      if (this.ScreenDefaultId.includes(this.selectedScreen?.id)) {
        return true;
      }
      return false;
    },
    showTasks() {
      if (this.isEditColumns && !this.isTCEScreen) {
        return true;
      }
      return false;
    },
    showCases() {
      if (this.isEditColumns && this.isTCEScreen) {
        return true;
      }
      if (this.isEditColumns && !this.isTCEScreen) {
        // This was not implemented now
        return false;
      }
      return false;
    },
  },
  mounted() {
    this.retrieveSavedSearchCharts();
    this.retrieveDisplayScreen();
    this.getDescriptionInitial();
    this.getProcessDescription();

    // Receives selected Option from launchpad Icons multiselect
    this.$root.$on("launchpadIcon", this.launchpadIconSelected);
  },
  methods: {
    /**
     * Get all information related to Launchpad Settings Modal
     */
    getLaunchpadSettings() {
      ProcessMaker.apiClient
        .get(`process_launchpad/${this.processId}`)
        .then((response) => {
          const firstResponse = response.data.shift();
          const unparseProperties = firstResponse?.launchpad?.properties;
          const launchpadProperties = unparseProperties ? JSON.parse(unparseProperties) : "";
          if (launchpadProperties !== "" && "tabs" in launchpadProperties) {
            this.tabs = launchpadProperties.tabs;
          }
          if (launchpadProperties !== "" && "my_tasks_columns" in launchpadProperties) {
            this.myTasks.currentColumns = launchpadProperties.my_tasks_columns;
          }
          if (launchpadProperties !== "" && "my_cases_columns" in launchpadProperties) {
            this.myCases.currentColumns = launchpadProperties.my_cases_columns;
          }
          if (launchpadProperties && Object.keys(launchpadProperties).length > 0) {
            this.selectedSavedChart = this.getSelectedSavedChartJSONFromResult(launchpadProperties);
            this.selectedLaunchpadIcon = this.verifyProperty(launchpadProperties.icon)
              ? this.defaultIcon
              : launchpadProperties.icon;
            this.selectedLaunchpadIconLabel = this.verifyProperty(launchpadProperties.icon_label)
              ? this.defaultIcon
              : launchpadProperties.icon_label;
            this.selectedScreen = this.getSelectedScreenJSONFromResult(launchpadProperties);
            this.$refs["icon-dropdown"].setIcon(this.selectedLaunchpadIcon);
          } else {
            this.selectedSavedChart = this.getSelectedSavedChartJSON(this.defaultChart);
            this.selectedScreen = this.getSelectedScreenJSON(this.defaultScreen);
          }
          this.oldScreen = this.selectedScreen.id;
          // Load media into Carousel Container
          const mediaArray = firstResponse.media;
          const embedArray = firstResponse.embed;
          mediaArray.forEach((media) => {
            this.$refs["image-carousel"].convertImageUrlToBase64(media);
          });
          embedArray.forEach((media) => {
            this.$refs["image-carousel"].addEmbedFile(media);
          });
          this.$refs["image-carousel"].setProcessId(this.processId);
        });
    },
    getSelectedSavedChartJSONFromResult(launchpadProperties) {
      return {
        id: this.verifyProperty(launchpadProperties.saved_chart_id)
          ? this.defaultChart.id
          : launchpadProperties.saved_chart_id,
        title: this.verifyProperty(launchpadProperties.saved_chart_title)
          ? this.defaultChart.title
          : launchpadProperties.saved_chart_title,
      };
    },
    getSelectedScreenJSONFromResult(launchpadProperties) {
      return {
        id: this.verifyProperty(launchpadProperties.screen_id)
          ? this.defaultScreen.id
          : launchpadProperties.screen_id,
        uuid: this.verifyProperty(launchpadProperties.screen_uuid)
          ? this.defaultScreen.uuid
          : launchpadProperties.screen_uuid,
        title: this.verifyProperty(launchpadProperties.screen_title)
          ? this.defaultScreen.title
          : launchpadProperties.screen_title,
      };
    },
    getSelectedSavedChartJSON(defaultChart) {
      return {
        id: defaultChart.id,
        title: defaultChart.title,
      };
    },
    getSelectedScreenJSON(defaultScreen) {
      return {
        id: defaultScreen.id,
        uuid: defaultScreen.uuid,
        title: defaultScreen.title,
      };
    },
    /**
     * Verify if the property has any value
     */
    verifyProperty(property) {
      return property === undefined || property === null || property === "";
    },
    showModal() {
      this.subject = "";
      this.description = "";
      this.errors = "";
      this.getLaunchpadSettings();
      this.$refs["my-modal-save"].show();
    },
    closeModal() {
      this.$bvModal.hide("launchpadSettingsModal");
      this.errors = "";
    },
    hideModal() {
      this.$refs["my-modal-save"].hide();
      this.cleanTabLaunchpad();
    },
    cleanTabLaunchpad() {
      this.getProcessDescription();
      this.retrieveSavedSearchCharts();
      this.retrieveDisplayScreen();
      this.showVersionInfo = true;
      this.isSecondaryColor = false;
    },
    /**
     * Save Launchpad Settings if this modal is open in Modeler
     */
    saveLaunchpadSettings(data) {
      if (window.ProcessMaker.modeler) {
        window.ProcessMaker.modeler.launchpad = data;
        this.saveLaunchpadSettingsAlternatives(data);
      }
    },
    /**
     * Save Launchpad Settings in all alternatives
     */
    saveLaunchpadSettingsAlternatives(data) {
      if (window.parent[0]?.ProcessMaker?.modeler && window.parent[1]?.ProcessMaker?.modeler) {
        window.parent[0].ProcessMaker.modeler.launchpad = data;
        window.parent[1].ProcessMaker.modeler.launchpad = data;
      }
    },
    /**
     * Save description field in Process
     */
    saveProcessDescription() {
      if (!this.$refs["image-carousel"].checkImages()) return;
      this.dataProcess.imagesCarousel = this.$refs["image-carousel"].getImages();
      this.dataProcess.properties = JSON.stringify(
        {
          saved_chart_id: this.selectedSavedChart.id,
          saved_chart_title: this.selectedSavedChart.title,
          screen_id: this.selectedScreen.id,
          screen_uuid: this.selectedScreen.uuid,
          screen_title: this.selectedScreen.title,
          icon: this.selectedLaunchpadIcon,
          icon_label: this.selectedLaunchpadIconLabel,
          tabs: this.tabs,
          my_tasks_columns: this.myTasks.currentColumns,
          my_cases_columns: this.myCases.currentColumns,
        },
        null,
        1,
      );

      ProcessMaker.apiClient
        .put(`process_launchpad/${this.options.id}`, {
          imagesCarousel: this.dataProcess.imagesCarousel,
          description: this.dataProcess.description,
          properties: this.dataProcess.properties,
        })
        .then((response) => {
          ProcessMaker.alert(
            this.$t("The launchpad settings were saved."),
            "success",
            5,
            true,
          );
          const params = {
            indexImage: null,
            type: "add",
          };
          if (this.oldScreen !== this.selectedScreen.id) {
            ProcessMaker.EventBus.$emit(
              "reloadByNewScreen",
              this.selectedScreenId,
            );
          }
          ProcessMaker.EventBus.$emit("getLaunchpadImagesEvent", params);
          ProcessMaker.EventBus.$emit("getChartId", this.selectedSavedChart.id);
          this.customModalButtons[1].disabled = false;
          this.$emit("updateMyTasksColumns", this.myTasks.currentColumns);
          this.$emit("updateMyCasesColumns", this.myCases.currentColumns);
          this.saveLaunchpadSettings(response.data);
          this.hideModal();
        })
        .catch((error) => {
          console.error("Error: ", error);
        });
    },
    saveModal() {
      this.customModalButtons[1].disabled = true;
      this.dataProcess = this.process;
      // if method is not called from ProcessMaker core
      if (this.origin !== "core") {
        this.dataProcess = ProcessMaker.modeler.process;
      }
      this.saveProcessDescription();
    },
    /**
     * Initial method to retrieve Saved Search Charts and populate dropdown
     * Package Collections and Package SavedSearch always go together
     */
    retrieveSavedSearchCharts(query = "") {
      if (!ProcessMaker.packages.includes("package-collections")) return;
      const filter = query === "" || query === null ? "" : `&filter=${query}`;
      ProcessMaker.apiClient
        .get(
          "saved-searches?page=1&per_page=10&order_by=title&order_direction=asc"
            + "&has=charts&include=charts&get=id,title,charts.id,charts.title,charts.saved_search_id,type"
            + `${filter}`,
        )
        .then((response) => {
          if (response.data.data[0].charts) {
            const resultArray = response.data.data.flatMap((item) => {
              if (item.charts && Array.isArray(item.charts)) {
                return item.charts.map((chartItem) => ({
                  id: chartItem.id,
                  title: chartItem.title,
                }));
              }
              return [];
            });

            this.dropdownSavedCharts = [this.defaultChart].concat(resultArray);
          }
        })
        .catch((error) => {
          this.dropdownSavedCharts = [this.defaultChart];
        });
    },
    /**
     * Initial method to retrieve Screens and populate dropdown
     */
    retrieveDisplayScreen(query = "") {
      const filter = query === "" || query === null ? "" : `&filter=${query}`;
      ProcessMaker.apiClient
        .get(
          `screens?page=1&per_page=10&order_by=title&order_direction=asc&include=categories,category&exclude=config&type=DISPLAY${filter}`,
        )
        .then((response) => {
          if (response.data.data) {
            const resultArray = response.data.data.flatMap((item) => ({
              id: item.id,
              title: item.title,
              uuid: item.uuid,
            }));

            this.dropdownSavedScreen = [this.defaultScreen];
            if (isTceCustomization()) {
              this.dropdownSavedScreen = this.dropdownSavedScreen.concat(this.tceScreens);
            }
            this.dropdownSavedScreen = this.dropdownSavedScreen.concat(resultArray);
          }
        })
        .catch((error) => {
          this.dropdownSavedScreen = [this.defaultScreen];
        });
    },
    /**
     * Method to store initial data from process description field
     */
    getDescriptionInitial() {
      if (this.origin !== "core") {
        if (ProcessMaker.modeler?.process) {
          this.processDescriptionInitial = ProcessMaker.modeler.process.description;
        }
      } else {
        this.processDescriptionInitial = this.descriptionSettings;
      }
    },
    /**
     * Method to retrieve data from process description field
     */
    getProcessDescription() {
      if (this.origin !== "core") {
        if (ProcessMaker.modeler?.process) {
          this.processDescription = ProcessMaker.modeler.process.description;
          this.processId = ProcessMaker.modeler.process.id;
          if (ProcessMaker.modeler.process.description === "") {
            this.processDescription = this.processDescriptionInitial;
          }
        }
      } else {
        this.processDescription = this.descriptionSettings;
        this.processId = this.process.id;
        if (!this.processDescription) {
          this.processDescription = this.processDescriptionInitial;
        }
      }
    },
    launchpadIconSelected(iconData) {
      this.selectedLaunchpadIcon = iconData.value;
      this.selectedLaunchpadIconLabel = iconData.label;
    },
    /**
     * This method shows a modal window to edit the columns of the My Tasks list.
     * If you don't use the nextTick method, the modal will not be displayed correctly.
     */
    showEditColumn(type) {
      this.columnListing = type === "tasks" ? this.myTasks : this.myCases;
      this.typeListing = type;
      this.$refs.editColumnModal.showModal();
      this.$nextTick(() => {
        this.getMyColumns(type);
      });
    },
    async getMyColumns(type) {
      //this.columnListing.currentColumns = type === "tasks" ? this.myTasksColumns : this.myCasesColumns;
      console.log("myTasksColumns", JSON.stringify(this.myTasksColumns));
      console.log("myCasesColumns", JSON.stringify(this.myCasesColumns));
      if (this.isTCEScreen) {
        this.changeSelectedScreen();
      }

      await ProcessMaker.apiClient
        .get("saved-searches/columns")
        .then((response) => {
          if (response.data && response.data.default) {
            this.columnListing.defaultColumns = response.data.default;
            this.columnListing.defaultColumns.push({
              field: "options",
              label: "",
              sortable: false,
              width: 180,
            });
          }
          if (response.data) {
            if (response.data.available) {
              this.columnListing.availableColumns = response.data.available;

              // Merge all available and default columns; we use map to avoid duplicates.
              const allColumns = new Map([
                ...this.columnListing.defaultColumns.map((col) => [col.field, col]),
                ...this.columnListing.availableColumns.map((col) => [col.field, col]),
              ]);

              // Filter only those that are not in `currentColumns`.
              this.columnListing.availableColumns = [...allColumns.values()].filter(
                (column) => !this.columnListing.currentColumns.some(
                  (currentColumn) => currentColumn.field === column.field,
                ),
              );
            }
            if (response.data.data) {
              this.columnListing.dataColumns = response.data.data;
            }
          }
        });
    },
    updateColumns(columns, type) {
      console.log("updateColumns: ", this.myTasks.currentColumns,columns, type);
      if (type === "tasks") {
        this.myTasks.currentColumns = columns;
      } else {
        this.myCases.currentColumns = columns;
      }
    },
    changeSelectedScreen() {
      if (this.selectedScreen.id === 'tce-student') {
        this.myCases.currentColumns = this.getTceStudent();
        return;
      }
      if (this.selectedScreen.id === 'tce-college') {
        this.myCases.currentColumns = this.getTceCollege();
        return;
      }
      if (this.selectedScreen.id === 'tce-grants') {
        this.myCases.currentColumns = this.getTceGrants();
        return;
      }
      this.myCases.currentColumns = this.getDefaultColumns(); 
    },
    getDefaultColumns() {
      return [
        {
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 80,
        },
        {
          label: "Case title",
          field: "case_title",
          sortable: true,
          default: true,
          truncate: true,
          width: 220,
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
          width: 100,
          filter_subject: { type: "Status" },
        },
        {
          label: "Started",
          field: "initiated_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160,
        },
        {
          label: "Completed",
          field: "completed_at",
          format: "datetime",
          sortable: true,
          default: true,
          width: 160,
        },
      ];
    },
    /**
     * column = [
     *   'case_number'
     *   'case_title'
     *   'tasks'
     *   'status'
     *   'last_stage_name'
     *   'progress'
     *   'data.program.name'
     *   'data.program.type'
     *   'data.program.source'
     *   'data.program.deadline'
     *   'data.program.amount'
     *   'data.program.status'
     *  ];
     * @returns {Array}
     */
    getTceStudent() {
      return [
        {
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 80,
        },
        {
          label: "Case title",
          field: "case_title",
          sortable: true,
          default: true,
          truncate: true,
          width: 220,
        },
        {
          label: "Tasks",
          field: "active_tasks",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
          width: 80,
          filter_subject: { type: "Status" },
        },
        {
          label: "Last Stage Name",
          field: "last_stage_name",
          sortable: false,
          default: true,
          truncate: true,
          width: 110,
        },
        {
          label: "Progress",
          field: "progress",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Name",
          field: "data.name",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Type",
          field: "data.type",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Source",
          field: "data.source",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Deadline",
          field: "data.deadline",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Amount",
          field: "data.amount",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Status",
          field: "data.status",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
      ];
    },
    /**
     * column = [
     *   'case_number'
     *   'case_title'
     *   'tasks'
     *   'status'
     *   'last_stage_name'
     *   'progress'
     *   'data.program'
     *   'data.type'
     *   'data.source'
     *   'data.deadline'
     *   'data.amount'
     *  ];
     * @returns {Array}
     */
    getTceCollege() {
      return [
        {
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 80,
        },
        {
          label: "Case title",
          field: "case_title",
          sortable: true,
          default: true,
          truncate: true,
          width: 220,
        },
        {
          label: "Tasks",
          field: "active_tasks",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
          width: 80,
          filter_subject: { type: "Status" },
        },
        {
          label: "Last Stage Name",
          field: "last_stage_name",
          sortable: false,
          default: true,
          truncate: true,
          width: 110,
        },
        {
          label: "Progress",
          field: "progress",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Program",
          field: "data.program",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Type",
          field: "data.type",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Source",
          field: "data.source",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Deadline",
          field: "data.deadline",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Amount",
          field: "data.amount",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
      ];
    },
    /**
     * column = [
     *   'case_number'
     *   'case_title'
     *   'tasks'
     *   'status'
     *   'last_stage_name'
     *   'progress'
     *   'data.applicationId'
     *   'data.title'
     *   'data.department'
     *   'data.primaryInvestigator'
     *   'data.agency'
     *   'data.dueDate'
     * ];
     * @returns {Array}
     */
    getTceGrants() {
      return [
        {
          label: "Case #",
          field: "case_number",
          sortable: true,
          default: true,
          width: 80,
        },
        {
          label: "Case title",
          field: "case_title",
          sortable: true,
          default: true,
          truncate: true,
          width: 220,
        },
        {
          label: "Tasks",
          field: "active_tasks",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Status",
          field: "status",
          sortable: true,
          default: true,
          width: 80,
          filter_subject: { type: "Status" },
        },
        {
          label: "Last Stage Name",
          field: "last_stage_name",
          sortable: false,
          default: true,
          truncate: true,
          width: 110,
        },
        {
          label: "Progress",
          field: "progress",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Application Id",
          field: "data.applicationId",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Title",
          field: "data.title",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Department",
          field: "data.department",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Primary Investigator",
          field: "data.primaryInvestigator",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Agency",
          field: "data.agency",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
        {
          label: "Due Date",
          field: "data.dueDate",
          sortable: false,
          default: true,
          truncate: true,
          width: 100,
        },
      ];
    },
  },
};
</script>

<style lang="css" scoped>
label {
  color: #556271;
  margin-top: 16px;
  margin-bottom: 4px;
  font-family: "Open Sans", sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 22px;
  letter-spacing: 0px;
  text-align: left;
}
.modal-title div {
  color: #556271;
  font-family: "Open Sans", sans-serif;
  font-size: 21px;
  font-weight: 400;
  line-height: 29px;
  letter-spacing: -0.02em;
  text-align: left;
}
.modal-header {
  align-items: center;
}
.modal-footer {
  margin-top: 0px;
  padding: 20px 24px;
}
.modal-footer .btn-outline-secondary {
  color: #556271;
  background-color: white;
  margin: 0px;
  width: 90px;
  height: 40px;
  font-family: "Open Sans", sans-serif;
  font-size: 16px;
  font-weight: 600;
  line-height: 24px;
  letter-spacing: -0.02em;
  text-align: center;
  padding: 0px 15px;
  border-radius: 4px;
  gap: 6px;
  border: 1px solid #6a7888;
}
.modal-footer .btn-secondary {
  color: white;
  background-color: #6a7888;
  width: 99px;
  height: 40px;
  margin: 0px;
  margin-left: 7px;
  font-family: "Open Sans", sans-serif;
  font-size: 16px;
  font-weight: 600;
  line-height: 24px;
  letter-spacing: -0.02em;
  text-align: center;
  padding: 0px 15px;
  border-radius: 4px;
  gap: 6px;
}
.text-info-custom {
  color: #556271;
  margin-bottom: 17px;
  font-family: "Open Sans", sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 22px;
  letter-spacing: 0px;
  text-align: left;
}
.custom-row {
  margin: 16px 0px;
}
.input-custom {
  height: 40px;
  color: #556271;
  background: white;
  border-radius: 4px;
  border: 1px solid #cdddee;
  font-family: "Open Sans", sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 21.79px;
  letter-spacing: -0.02em;
  gap: 6px;
}
.modal-dialog,
.modal-content {
  min-width: 800px;
}
.options-launchpad {
  width: 350px;
}
.modal-content-custom {
  padding: 11px 8px 0px 8px;
}
</style>

<style lang="scss">
.multiselect-chart.custom-multiselect,
.multiselect-screen.custom-multiselect {
  .input-group {
    width: 100%;
  }
  .multiselect,
  .multiselect__tags {
    height: 40px;
    min-height: 40px;
    max-height: 40px;
    border-radius: 4px;
    border-color: #cdddee;
  }
  .multiselect__tags {
    padding: 9px 12px;
  }
  .multiselect__single {
    width: 239px;
    height: 20px;
    overflow: hidden;
    text-align: left;
    text-overflow: ellipsis;
    font-family: "Open Sans", sans-serif;
    font-size: 16px;
    font-weight: 400;
    line-height: 21.79px;
    letter-spacing: -0.02em;
    color: #556271;
    padding-left: 0px;
  }
  .multiselect__select:before {
    border-width: 4px 4px 0 4px;
    border-color: #556271 transparent;
  }
  .multiselect__option--selected {
    font-weight: 400;
    background: white;
    color: #556271;
  }
  .multiselect__input::placeholder {
    color: #ebeef2;
    color: #556271;
  }
  .multiselect__input {
    max-width: 239px;
    height: 20px;
    font-family: "Open Sans", sans-serif;
    font-size: 16px;
    font-weight: 400;
    line-height: 21.79px;
    letter-spacing: -0.02em;
    color: #556271;
    padding-left: 0px;
  }
  .multiselect__option--highlight {
    background: #ebeef2;
    color: #556271;
  }
  .multiselect__content-wrapper {
    max-height: 200px !important;
    position: static;
  }

  .multiselect__option {
    color: #556271;
    padding: 12px;
    font-family: "Open Sans", sans-serif;
    font-size: 16px;
    font-weight: 400;
    line-height: 21.79px;
    letter-spacing: -0.02em;
    text-align: left;
  }
}
.modal-content-custom .column-container {
  overflow-y: auto;
}
</style>
