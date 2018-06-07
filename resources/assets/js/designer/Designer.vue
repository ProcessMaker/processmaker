<template>
    <div id="designer-container">
        <component :is="modalComponent" :if="modalComponent" @hidden="onHidden"></component>
        <toptoolbar ref="toptoolbar"></toptoolbar>
        <div id="designer-subcontainer">
            <toolbar ref="toolbar"></toolbar>
            <div class="canvas-container">
                <crown ref="crown"></crown>
                <svgcanvas ref="svgcanvas"></svgcanvas>
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
import modalPermissionsAdd from "./components/modals/modal-permissions-add";

import modalPublicFileAdd from "./components/modals/modal-public-file-add";

import modalInputDocumentAdd from "./components/modals/modal-input-document-add";
//
import modalFormsAdd from "./components/modals/modal-forms-add";

// This is out Cron for every shape
import crown from "./components/crown";

export default {
  components: {
    svgcanvas,
    toolbar,
    toptoolbar,
    designerobjectsmenu,
    modalPermissionsAdd,
    ///camel case because vue will transform it into dash's
    modalPublicFileAdd,
    modalInputDocumentAdd,
    modalFormsAdd,
    crown
  },
  data() {
    return {
      modalComponent: null
    }
  },
  created() {
    // Listen for opening an add dialog
    EventBus.$on("open-add-dialog", this.openAddDialog);
  },
  methods: {
    openAddDialog(key) {
      // @todo Replace this with dynamic modal generation once we have all modals in place
      // We're not doing this now so we can have visual alert feedback when a modal isn't implemented
      switch(key) {
        case 'permissions':
          this.modalComponent = 'modal-permissions-add'
          break;
        case 'public-files':
          this.modalComponent = 'modal-public-file-add'
          break;
        case 'forms':
          this.modalComponent = 'modal-forms-add'
          break;
        case 'input-documents':
          this.modalComponent = 'modal-input-document-add'
          break;
        default:
          alert(key + ' add modal not yet implemented.')
      }
    },
    onHidden(){
      this.modalComponent = null
    },
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
