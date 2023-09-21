<template>
  <b-card-text>
  <b-card class="no-border">
    <slot name="select-template-slot" class="custom-button custom-text"></slot>
  </b-card>
  <b-card class="no-border">
    <b-button
              variant="primary"
              size="sm"
              class="custom-button custom-text"
              block
              @click="callURL()"
            >
              {{ asset_name_all }}
              <i class="fas fa-arrow-right ml-2 align-middle"></i>
            </b-button>
  </b-card>
</b-card-text>
</template>

<script>
import SelectTemplateModal from "../../components/templates/SelectTemplateModal.vue";
import CreateProcessModal from "../components/CreateProcessModal.vue";
import ProcessesListing from "../components/ProcessesListing.vue";
import CategorySelect from "../categories/components/CategorySelect.vue";
import CreateScriptModal from "../../../js/processes/scripts/components/CreateScriptModal.vue";

export default {
  components: {
    SelectTemplateModal,
    CreateProcessModal,
    ProcessesListing,
    CategorySelect,
    CreateScriptModal,
  },
  props: {
    asset_name_all: String,
    urlPath: String
  },
  data() {
    return { 
      countCategories: 2,
    };
  },
  methods: {
    callURL() {
      //window.location.href = `http://172.16.3.48:9001/${assetName}`; 
      //window.open(this.url, '_self');
      //window.location.href = this.urlPath; 
      //console.log('callURL:', this.urlPath);
      //console.log('NAme:', this.asset_name_all);
      
      const baseURL = window.location.origin;
      console.log('URL Base:', baseURL);
      window.location.href = baseURL + this.urlPath; 
    }
    
  },
};
</script>

<style scoped>
.no-border {
  border: none !important;
}

/* Establece el ancho de los botones al 100% del card interno */
.b-card .btn {
  width: 100% !important;
}

/* Elimina el padding y el margen de los b-card para que no afecten el tamaño del b-card padre */
.b-card {
  padding: 0 !important;
  margin: 0 !important;
}

/* Asegura que no haya estilos heredados afectando a los botones */
.custom-button,
.custom-text {
  padding: 0 !important;
  margin: 0 !important;
  display: block !important;
}
</style>
