<template>
    <div id="designer-container">
        <component :is="modalComponent" :if="modalComponent" @hidden="onHidden" :processId="processId"
                   :selectedElement="selectedElement"></component>
        <toptoolbar ref="toptoolbar" :title="processTitle"></toptoolbar>
        <toolbar ref="toolbar"></toolbar>
        <div id="designer-subcontainer">
            <div class="canvas-container" @scroll="onScroll">
                <crown ref="crown"></crown>
                <svgcanvas :processId="processId" :bpmn="bpmn" ref="svgcanvas"></svgcanvas>
            </div>
            <designerobjectsmenu></designerobjectsmenu>
        </div>
    </div>
</template>


<script>
// Import our designer event-bus
import EventBus from "./lib/event-bus";
// Import our top-level components
// Designer is our overall canvas tool
import svgcanvas from "./components/svgcanvas";
// This is our toolbar palette
import toolbar from "./components/toolbar";
// This is our top toolbar with process title and process options
import toptoolbar from "./components/toptoolbar";
// This is our objects menu with nested object-menu items components
import designerobjectsmenu from "./components/designer-objects-menu";
// @todo Figure out a way to add these modals to the properties of components
import modalCreateDatabaseAdd from "./components/modals/modal-create-database-add";
import modalCreateOutputAdd from "./components/modals/modal-create-output-add";
import modalCreateTemplateAdd from "./components/modals/modal-create-template-add";
import modalCreateScriptAdd from "./components/modals/modal-create-script-add";
import modalFormsAdd from "./components/modals/modal-forms-add";
import modalInputDocumentAdd from "./components/modals/modal-input-document-add";
import modalPermissionsAdd from "./components/modals/modal-permissions-add";
import modalPublicFileAdd from "./components/modals/modal-public-file-add";
import modalMessageTypes from "./components/modals/modal-message-types";
import modalFormsList from "./components/modals/modal-forms-list";
import ModalScriptTask from "./components/modals/modal-script-task";
import modalTaskConfiguration from "./components/modals/modal-task-configuration";

//Modal list
import modalForms from "./components/modalList/modal-forms-list";
import modalOutputDocuments from "./components/modalList/modal-output-documents";
import ModalInputDocumentList from "./components/modalList/modal-input-document-list";
import ModalScriptsList from "./components/modalList/modal-scripts-list";

// This is out Cron for every shape
import crown from "./components/crown";
import actions from "./actions";

export default {
  props: ["processId", "processTitle"],
  components: {
    crown,
    designerobjectsmenu,
    modalCreateDatabaseAdd,
    modalCreateOutputAdd,
    modalCreateTemplateAdd,
    modalCreateScriptAdd,
    modalFormsAdd,
    modalMessageTypes,
    modalInputDocumentAdd,
    modalPermissionsAdd,
    modalPublicFileAdd,
    modalOutputDocuments,
    modalFormsList,
    modalTaskConfiguration,
    svgcanvas,
    toolbar,
    toptoolbar,
    modalForms,
    ModalInputDocumentList,
    ModalScriptsList,
    ModalScriptTask
  },
  data() {
    return {
      modalComponent: null,
      bpmn: {},
      selectedElement: {},
      autoSaveTime: 15000 // miliseconds
    };
  },
  created() {
    // Listen for opening an add dialog
    EventBus.$on("open-add-dialog", this.openAddDialog);
    EventBus.$on("open-title-dialog", this.openTitleDialog);
  },
  methods: {
    openAddDialog(key) {
      let element = this.$refs.svgcanvas.builder.selection[0];
      this.selectedElement = element ? element.getOptions() : undefined;
      // @todo Replace this with dynamic modal generation once we have all modals in place
      // We're not doing this now so we can have visual alert feedback when a modal isn't implemented
      switch (key) {
        case "permissions":
          this.modalComponent = "modal-permissions-add";
          break;
        case "public-files":
          this.modalComponent = "modal-public-file-add";
          break;
        case "forms":
          this.modalComponent = "modal-forms-add";
          break;
        case "formslist":
          this.modalComponent = "modal-forms-list";
          break;
        case "message-types":
          this.modalComponent = "modal-message-types";
          break;
        case "database-connections":
          this.modalComponent = "modal-create-database-add";
          break;
        case "input-documents":
          this.modalComponent = "modal-input-document-add";
          break;
        case "output-documents":
          this.modalComponent = "modal-create-output-add";
          break;
        case "scripts":
          this.modalComponent = "modal-create-script-add";
          break;
        case "templates":
          this.modalComponent = "modal-create-template-add";
          break;
        case "open":
          this.modalComponent = "modal-create-template-add";
          break;
        case "show-element-configuration":
          switch (this.selectedElement.type) {
            case "scripttask":
              this.modalComponent = "modal-script-task";
              break;
          }
        case "task-configuration":
          this.modalComponent = "modal-task-configuration";
          break;
        default:
          alert(key + " add modal not yet implemented.");
      }
    },
    openTitleDialog(key) {
      switch (key) {
        case "forms":
          this.modalComponent = "modal-forms";
          break;
        case "input-documents":
          this.modalComponent = "modal-input-document-list";
          break;
        case "output-documents":
          this.modalComponent = "modal-output-documents";
          break;
        case "scripts":
          this.modalComponent = "modal-scripts-list";
          break;
        default:
          alert(key + " Behavior TBD");
      }
    },
    onHidden() {
      this.modalComponent = null;
    },
    onScroll() {
      let action = actions.designer.crown.hide();
      EventBus.$emit(action.type, action.payload);
    },
    autoSave() {
      setInterval(() => {
        let action = actions.bpmn.save();
        EventBus.$emit(action.type, action.payload);
      }, this.autoSaveTime);
    }
  },
  mounted() {
    ProcessMaker.apiClient
      .get(`processes/${this.processId}/bpmn`, {
        params: {}
      })
      .then(response => {
        let action = actions.designer.bpmn.update(response.data);
        EventBus.$emit(action.type, action.payload);
      });
    this.autoSave();
  }
};
</script>

<style lang="scss" scoped>
#designer-container {
  // Width and height is set to 100% to fill the outside container
  max-height: 100%;
  min-height: 100%;
  max-width: 100%;
  min-width: 100%;
  flex: 1;
  // We will flex container the items
  display: flex;
  flex-direction: column;
  background-color: green;
  #designer-subcontainer {
    display: flex;
    flex: 1;
    flex-direction: row;
    min-width: 100%;
    max-width: 100%;
    min-height: 100%;
    max-height: 100%;
    .canvas-container {
      flex: 1;
      overflow: auto;
    }
  }
}
</style>
