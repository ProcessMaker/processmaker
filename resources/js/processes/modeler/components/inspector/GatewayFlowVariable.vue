<template>
    <div v-if="isConditionedFlow">
      <h5>{{ $t('Flow Variable') }}</h5>
      <small class="form-text text-muted">{{$t("If the expression evaluates to true, create or update the following variable")}}</small>
      <div class="form-group">
          <label>{{ $t("Variable Name") }}</label>
          <input class="form-control" ref="variableName" type="text"
                   v-model="config.update_data.variable">
          <small class="form-text text-muted">{{$t("A variable name is a symbolic name to reference information.")}}</small>
        </div>

        <div class="form-group">
          <label>{{ $t("Value") }}</label>
          <input class="form-control" ref="expression" type="text"
                   v-model="config.update_data.expression">
        </div>
    </div>
</template>

<script>

  import ScreenSelect from "./ScreenSelect";
  export default {
    components: {ScreenSelect},
    props: ["value"],
    data() {
      return {
        config: {
          update_data: {
            variable: '',
            expression: ''
          }
        },
      };
    },

    watch: {
      config: {
        deep: true,
        handler() {
          this.updateConfig();
        }
      },
      value() {
        this.loadConfig();
      },
    },

    computed: {
      node() {
        return this.$root.$children[0].$refs.modeler.highlightedNode.definition;
      },
      modeler() {
        return this.$root.$children[0].$refs.modeler;
      },
      highlightedNode() {
        return this.modeler.highlightedNode;
      },
      definition() {
        return this.highlightedNode.definition;
      },
      isConditionedFlow() {
        return this.node.sourceRef
            && this.node.sourceRef.$type
            && this.node.sourceRef.$type.endsWith('ExclusiveGateway');
      },
    },

    methods: {
      loadConfig() {
        if (!this.isConditionedFlow) {
          return;
        }
        const config = JSON.parse(_.get(this.node, 'config', '{}'));
        Object.keys(config).forEach(key => {
          Vue.set(this.config, key, config[key]);
        });
      },
      updateConfig() {
        if (!this.isConditionedFlow) {
          return;
        }
        Vue.set(this.node, 'config', JSON.stringify(this.config));
      },
    },

    mounted() {
      this.loadConfig();
    }
  };
</script>
