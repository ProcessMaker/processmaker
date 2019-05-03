<template>
  <div id="modeler-app">
    <div class="navbar">
      <div>{{process.name}}</div>
      <div>
          <a v-b-modal="'uploadmodal'"><i class="fas fa-upload fa-fw"></i></a>
          <a @click="saveBpmn"><i class="fas fa-save fa-fw"></i></a>
      </div>
    </div>
    <div class="modeler-container">
      <modeler ref="modeler" @validate="validationErrors = $event" />
    </div>
    <statusbar>
      <validation-status :validation-errors="validationErrors"/>
    </statusbar>
    <b-modal ref="uploadmodal" id="uploadmodal" centered :title="$t('Upload BPMN File')">
      <file-upload @input-file="handleUpload">
        <button class="btn btn-secondary"><i class="fas fa-upload fa-fw"></i>{{ $t('Upload file') }}</button>
      </file-upload>
      <div slot="modal-footer">
        <button class="btn btn-outline-secondary" @click="closeModal">{{ $t('Cancel') }}</button>
      </div>
    </b-modal>
  </div>
</template>


<script>
import { Modeler, Statusbar, ValidationStatus } from "@processmaker/spark-modeler";
import { library } from "@fortawesome/fontawesome-svg-core";
import FileUpload from 'vue-upload-component';

import {
  faCheckCircle,
  faTimesCircle,
  faSave,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import moment from 'moment';

library.add(faSave)

const reader = new FileReader();

export default {
  name: "ModelerApp",
  components: {
    Modeler,
    Statusbar,
    FontAwesomeIcon,
    ValidationStatus,
    FileUpload,
  },
  data() {
    return {
      process: window.ProcessMaker.modeler.process,
      validationErrors: {},
    };
  },
  computed: {
    lastSaved() {
      return moment(this.process.updated_at).fromNow();
    }
  },
  mounted() {
    reader.onloadend = () => {
      this.$refs.modeler.loadXML(reader.result);
      this.$refs.uploadmodal.hide();
    };

    ProcessMaker.$modeler = this.$refs.modeler;

    window.ProcessMaker.EventBus.$on('modeler-change', this.refreshSession);
  },
  methods: {
    refreshSession: _.throttle(function() {
      ProcessMaker.apiClient({
          method: 'POST',
          url: '/keep-alive',
          baseURL: '/',
        })
    }, 60000),
    handleUpload(fileObject) {
      if (!fileObject) {
        return;
      }

      reader.readAsText(fileObject.file);
    },
    closeModal() {
      this.$refs.modal.hide()
    },
    getTaskNotifications() {
      var notifications = {};
      this.$refs.modeler.nodes.forEach(function(node) {
        let id = node.definition.id;
        if (node.notifications !== undefined) {
          notifications[id] = node.notifications;
        }
      });
      return notifications;
    },
    saveBpmn() {
      this.$refs.modeler.toXML((err, xml) => {
        if(err) {
          ProcessMaker.alert("There was an error saving: " + err, 'danger');
        } else {
          // lets save
          // Call process update
          var data = {
            name: this.process.name,
            description: this.process.description,
            task_notifications: this.getTaskNotifications(),
            bpmn: xml
          };
          ProcessMaker.apiClient.put('/processes/' + this.process.id, data)
          .then((response) => {
            this.process.updated_at = response.data.updated_at;
            // Now show alert
            ProcessMaker.alert(this.$t('The process was saved.'), 'success');
          })
          .catch((err) => {
            const message = err.response.data.message;
            const errors = err.response.data.errors;
            ProcessMaker.alert(message, 'danger');
            console.log(errors);
          })
        }

      });
    }

   }
};
</script>


<style lang="scss">
body,
html {
  margin: 0;
  padding: 0;
  width: 100vw;
  max-width: 100vw;
  height: 100vh;
  max-height: 100vh;
}

#modeler-app {
  font-family: 'Open Sans', Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  display: flex;
  flex-direction: column;
  width: 100vw;
  max-width: 100vw;
  height: 100vh;
  max-height: 100vh;

  .modeler-container {
    flex-grow: 1;
    overflow: hidden;
  }

  .navbar {
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: #3397e1;
    color: white;
    border-bottom: 1px solid grey;
    padding-right: 16px;
    padding-left: 16px;

    .actions {
      button {
        border-radius: 4px;
        display: inline-block;
        padding-top: 4px;
        padding-bottom: 4px;
        padding-left: 8px;
        padding-right: 8px;
        background-color: grey;
        color: white;
        border-width: 1px;
        border-color: darkgrey;
        margin-right: 8px;
        font-weight: bold;
      }
    }
  }
}
</style>
