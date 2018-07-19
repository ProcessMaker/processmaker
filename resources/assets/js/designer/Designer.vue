<template>
    <div id="designer-container">
        <component :is="modalComponent" :if="modalComponent" @hidden="onHidden"></component>
        <toptoolbar ref="toptoolbar"></toptoolbar>
        <toolbar ref="toolbar"></toolbar>
        <div id="designer-subcontainer">
            <div class="canvas-container" @scroll="onScroll">
                <crown ref="crown"></crown>
                <svgcanvas :bpmn="bpmn" ref="svgcanvas"></svgcanvas>
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
import modalCreateTriggerAdd from "./components/modals/modal-create-trigger-add";
import modalFormsAdd from "./components/modals/modal-forms-add";
import modalInputDocumentAdd from "./components/modals/modal-input-document-add";
import modalPermissionsAdd from "./components/modals/modal-permissions-add";
import modalPublicFileAdd from "./components/modals/modal-public-file-add";
import modalVariablesAdd from "./components/modals/modal-variables-add";
import modalMessageTypes from "./components/modals/modal-message-types";
import modalOutputDocuments from "./components/modals/modal-output-documents"

// This is out Cron for every shape
import crown from "./components/crown"
import actions from "./actions"

export default {
  props: [
    'processUid'
  ],
  components: {
    crown,
    designerobjectsmenu,
    modalCreateDatabaseAdd,
    modalCreateOutputAdd,
    modalCreateTemplateAdd,
    modalCreateTriggerAdd,
    modalFormsAdd,
    modalMessageTypes,
    modalInputDocumentAdd,
    modalPermissionsAdd,
    modalPublicFileAdd,
    modalVariablesAdd,
    modalOutputDocuments,
    svgcanvas,
    toolbar,
    toptoolbar
  },
  data() {
    return {
      modalComponent: null,
      bpmn: {}
    }
  },
  created() {
    // Listen for opening an add dialog
    EventBus.$on("open-add-dialog", this.openAddDialog);
    EventBus.$on("open-title-dialog", this.openTitleDialog);
  },
  methods: {
    openAddDialog(key) {
      // @todo Replace this with dynamic modal generation once we have all modals in place
      // We're not doing this now so we can have visual alert feedback when a modal isn't implemented
      switch(key) {
        case 'permissions':
          this.modalComponent = 'modal-permissions-add'
          break;
        case 'variables':
          this.modalComponent = 'modal-variables-add'
          break;
        case 'public-files':
          this.modalComponent = 'modal-public-file-add'
          break;
        case 'forms':
          this.modalComponent = 'modal-forms-add'
          break;
        case 'message-types':
          this.modalComponent = 'modal-message-types'
          break;
        case 'database-connections':
          this.modalComponent = 'modal-create-database-add'
          break;
        case 'input-documents':
          this.modalComponent = 'modal-input-document-add'
          break;
        case 'output-documents':
          this.modalComponent = 'modal-create-output-add'
          break;
        case 'triggers':
          this.modalComponent = 'modal-create-trigger-add'
          break;
        case 'templates':
          this.modalComponent = 'modal-create-template-add'
          break;
        default:
          alert(key + ' add modal not yet implemented.')
      }
    },
    openTitleDialog(key){
      switch(key){
        case 'output-documents':
          this.modalComponent = 'modal-output-documents'
          break;
        default:
          alert(key + ' Behavior TBD')
      }
    },
    onHidden(){
      this.modalComponent = null
    },
    onScroll(){
        let action = actions.designer.crown.hide()
        EventBus.$emit(action.type, action.payload)
    }
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
