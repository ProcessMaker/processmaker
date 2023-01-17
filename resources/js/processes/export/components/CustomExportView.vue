<template>
    <div>
    {{ groups.length }}
    <container ref="container">
        <container-page ref="home" parent active :header="rootAsset.name">
            <ProcessesView
                @processesView="showProcessesView"
                :processInfo="rootAsset"
                :processName="processName"
            />
        </container-page>
        <template v-for="group in groups">
        <container-page :header="group.type" icon="user">
            <!-- <ScriptsView
                @processesView="showProcessesView"
                :items="group.items"
            /> -->
            foobar
        </container-page>
        </template>
    </container>




    <hr>
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
import { Container, ContainerPage } from "SharedComponents";
import DataProvider from '../DataProvider';

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
        Container,
        ContainerPage,
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

            rootAsset: {},
            groups: [],
        }
    },
    methods: {
        showProcessesView() {
            
            this.$refs.container.goTo(0);
            // this.$refs.home.setToActive();
            // this.currentProcessElement = ProcessesView;
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
        DataProvider.getManifest(this.processId)
        .then((response) => {
            console.log("OUTPUT from data provider", response);
            // console.log('response', response);
            // let payload = response.data;
            // console.log('payload', payload);
            // let manifest = payload.manifest;
            // console.log('manifest', manifest);
            // let rootUuid = manifest.root;
            // console.log('rootUuid', rootUuid);
            // this.processInfo = manifest.export[rootUuid];
            // console.log(this.processInfo);
            this.rootAsset = response.root;
            this.groups = response.groups;
        })
        .catch((error) => {
          console.log(error);
            ProcessMaker.alert(error, "danger");
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
