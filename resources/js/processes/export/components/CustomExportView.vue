<template>
  <div>
    <container :sidenav="sidenav">
      <template v-slot:default="slotProps">
        <container-page :active="slotProps.activeIndex === 0">
          <ProcessesView
            :process-info="rootAsset"
            :groups="groups"
            :process-name="rootAsset.name"
            :process-id="processId"
          />
        </container-page>
        <container-page v-for="(group, i) in groups" :key="i" :active="slotProps.activeIndex === i + 1">
          <ScriptsView
            :group="group"
            :items="group.items"
            :process-name="rootAsset.name"
          />
        </container-page>
      </template>
    </container>
  </div>
</template>

<script>
import { Container, ContainerPage } from "SharedComponents";
import ProcessesView from "./process-elements/ProcessesView.vue";
import ScriptsView from "./process-elements/ScriptsView.vue";
import DataProvider from "../DataProvider";

export default {
  components: {
    ProcessesView,
    ScriptsView,
    Container,
    ContainerPage,
  },
  props: {
    processName: {},
    processId: {},
  },
  mixins: [],
  data() {
    return {
      rootAsset: {},
      groups: [],
    };
  },
  computed: {
    sidenav() {
      let items = [
        { title: this.rootAsset.name, icon: null, },
      ];

      this.groups.forEach(group => {
        items.push({
          title: group.typePlural,
          icon: group.icon,
        });
      });

      return items;
    },
  },
  mounted() {
    if (this.$root.isImport) {

        // TESTING
        // put this.$root.manifest into local storage if it's not null
        // if (this.$root.rootUuid !== '') {
        //     localStorage.setItem('manifest', JSON.stringify(this.$root.manifest));
        //     localStorage.setItem('rootUuid', this.$root.rootUuid);
        //     localStorage.setItem('ioState', JSON.stringify(this.$root.ioState));
        // } else {
        //   this.$root.rootUuid = localStorage.getItem('rootUuid');
          
        //   let manifest = localStorage.getItem('manifest');
        //   if (manifest) {
        //     this.$root.manifest = JSON.parse(manifest);
        //   }

        //   let ioState = localStorage.getItem('ioState');
        //   if (ioState) {
        //     this.$root.ioState = JSON.parse(ioState);
        //   }

        // }
        // console.log('stuff', JSON.stringify(this.$root.rootUuid), JSON.stringify(this.$root.ioState));
        // END TESTING

        const formatted = DataProvider.formatAssets(this.$root.manifest, this.$root.rootUuid);
        this.rootAsset = formatted.root;
        this.groups = formatted.groups;
    } else {
      DataProvider.getManifest(this.processId)
        .then((response) => {
          this.rootAsset = response.root;
          this.groups = response.groups;
          this.$root.setInitialState(response.assets, response.rootUuid);
        })
        .catch((error) => {
          console.log(error);
          ProcessMaker.alert(error, "danger");
        });
    }
  },
  methods: {
  },
};
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
