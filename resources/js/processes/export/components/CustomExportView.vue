<template>
    <div class="custom-export-container container pt-3">
        <b-row>
            <b-col cols="3" class="border-right">
                <sidebar-navigation 
                ref="sidebar-navigation" 
                :processName="processName" 
                @scriptsView="showScriptsView"
                @screensView="showScreensView"
                @environmentVariablesView="showEnvironmentVariablesView"
                @signalsView="showSignalsView"
                @dataConnectorsView="showDataConnectorsView"
                @vocabulariesView="showVocabulariesView"
                ></sidebar-navigation>
            </b-col>
            <b-col cols="7" class="data-container">
                <div>
                    <KeepAlive>
                    <component 
                        :is="currentProcessElement"
                        @processesView="showProcessesView"
                        :processInfo="processInfo"
                        :processName="processName"
                        ></component>
                    </KeepAlive>
                </div>
            </b-col>
      <b-col cols="2" />
        </b-row>
    </div>
</template>

<script>
import SidebarNavigation from "../../../components/shared/SidebarNavigation.vue";
import ProcessesView from "./process-elements/ProcessesView.vue";
import ScriptsView from "./process-elements/ScriptsView.vue";
import ScreensView from "./process-elements/ScreensView.vue";
import EnvironmentVariablesView from "./process-elements/EnvironmentVariablesView.vue";
import SignalsView from "./process-elements/SignalsView.vue";
import DataConnectorsView from "./process-elements/DataConnectorsView.vue";
import VocabulariesView from "./process-elements/VocabulariesView.vue";

export default {
    components: {
        SidebarNavigation,
        ProcessesView,
        ScriptsView,
        ScreensView,
        EnvironmentVariablesView,
        SignalsView,
        DataConnectorsView,
        VocabulariesView,
    },
    props: ["processName",
    "processId",
    ],
    mixins: [],
    data() {
        return {
            currentProcessElement: "ProcessesView",
            processElements: ["ProcessesView",
            "ScriptsView",
            "ScreensView",
            "EnvironmentVariablesView",
            "SignalsView",
            "DataConnectorsView",
            "VocabulariesView"],
            processInfo: {},
        }
    },
    methods: {
        showProcessesView() {
            this.currentProcessElement = ProcessesView;
        },
        showScriptsView() {
            this.currentProcessElement = ScriptsView;
        },
        showScreensView() {
            this.currentProcessElement = ScreensView;
        },
        showEnvironmentVariablesView() {
            this.currentProcessElement = EnvironmentVariablesView;
        },
        showSignalsView() {
            this.currentProcessElement = SignalsView;
        },
        showDataConnectorsView() {
            this.currentProcessElement = DataConnectorsView;
        },
        showVocabulariesView() {
            this.currentProcessElement = VocabulariesView;
        }
    },
    mounted() {
        ProcessMaker.apiClient({
            url: `export/process/tree/${this.processId}`,
            method: "GET",
        })
        .then((response) => {
            console.log('response', response);
            let payload = response.data;
            console.log('payload', payload);
            let manifest = payload.manifest;
            console.log('manifest', manifest);
            let rootUuid = manifest.root;
            console.log('rootUuid', rootUuid);
            this.processInfo = manifest.export[rootUuid];
            console.log(this.processInfo);
        })
        .catch((error) => {
            ProcessMaker.alert(error.response.data.message, "danger");
        });
    }
}
</script>

<style lang="scss" scoped>
@import "../../../../sass/variables";

.custom-export-container {
    // max-width: 1600px;
    display: block;
    margin-left: auto;
    margin-right: auto;
    background-color: $light;
}

h2 {
  text-align: left;
}
</style>
