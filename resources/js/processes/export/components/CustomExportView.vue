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
                    <component :is="currentProcessElement" :processName="processName"></component>
                    </KeepAlive>
                    <div class="pt-3 card-footer bg-light" align="right">
                        <button type="button" class="btn btn-outline-secondary">
                            {{ $t("Cancel") }}
                        </button>
                        <button type="button" class="btn btn-primary ml-2">
                            {{ $t("Export") }}
                        </button>
                    </div>
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
    props: ["processName"],
    components: {
        SidebarNavigation,
        ProcessesView,
        ScriptsView,
        ScreensView,
        EnvironmentVariablesView,
        SignalsView,
        DataConnectorsView,
        VocabulariesView,
        // CustomExportOverview
    },
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
        }
    },
    methods: {
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
