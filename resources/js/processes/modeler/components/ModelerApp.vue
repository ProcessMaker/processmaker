<template>
  <div id="modeler-app">
    <div class="navbar">
      <div>{{process.name}}</div>
      <div class="actions">
          <font-awesome-icon @click="saveBpmn" icon="save">Save</font-awesome-icon>
      </div>
    </div>
    <div class="modeler-container">
      <modeler ref="modeler" />
    </div>
    <status-bar>
        <template slot="secondary">
            Last Saved: {{lastSaved}}
        </template>
      {{statusText}}
      <font-awesome-icon :style="{color: statusColor}" :icon="statusIcon" />
    </status-bar>

  </div>    
</template>


<script>
import {Modeler, StatusBar} from "@processmaker/modeler";
import { library } from "@fortawesome/fontawesome-svg-core";

import {
  faCheckCircle,
  faTimesCircle,
  faSave,
} from "@fortawesome/free-solid-svg-icons";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import moment from 'moment';

library.add(faSave)

export default {
  name: "ModelerApp",
  components: {
    Modeler,
    StatusBar,
    FontAwesomeIcon
  },
  data() {
    return {
      process: window.ProcessMaker.modeler.process,
      statusText: "No errors detected",
      statusIcon: faCheckCircle,
      statusColor: "green"
    };
  },
  computed: {
      lastSaved() {
          return moment(this.process.updated_at).fromNow()
      }
  },
  mounted() {
    // Emit our lifecycle events to allow customizations to modify the modeler
    // if needed
    //ProcessMaker.EventBus.$emit('modeler-init', this.$refs.modeler);
    //ProcessMaker.EventBus.$emit('modeler-start', this.$refs.modeler);
  },
  methods: {
    saveBpmn() {
      this.$refs.modeler.toXML((err, xml) => {
        if(err) {
          alert("There was an error saving: " + err);
        } else {
          // lets save
          // Call process update
          ProcessMaker.apiClient.put('/processes/' + this.process.id, {
            name: this.process.name,
            description: this.process.description,
            bpmn: xml
          })
          .then((response) => {
            this.process.updated_at = response.data.updated_at;
            // Now show alert
            ProcessMaker.alert('Process Successfully Updated', 'success');
          })
          .catch((err) => {
            alert('ERROR: ' + err);
          })
        }

      });
    }

   }
};
</script>


<style lang="scss" scoped>
#modeler-app {
  font-family: "Open Sans", Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  display: flex;
  flex-direction: column;
  position: absolute;
  width: 100%;
  max-width: 100%;
  height: 100%;
  max-height: 100%;
  .modeler-container {
    flex-grow: 1;
    overflow: hidden;
  }
  .navbar {
    font-weight: bold;
    height: 42px;
    min-height: 42px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 1.2em;
    background-color: #b6bfc6;

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
