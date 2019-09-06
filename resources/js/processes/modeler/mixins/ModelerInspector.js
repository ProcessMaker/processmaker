export default {
    computed: {
        modeler() {
            return this.$root.$children[0].$refs.modeler;
        },
        highlightedNode() {
            return this.modeler.highlightedNode;
        },
        definition() {
            return this.highlightedNode.definition;
        },
        modelerId() {
            return this.highlightedNode._modelerId;
        }
    },
    watch: {
        modelerId: {
            immediate: true,
            handler() {
                if (this.config === undefined) {
                    return;
                }
                try {
                    Object.assign(this.config, this.defaultConfig, JSON.parse(this.definition.config));
                } catch (e) {
                    Object.assign(this.config, this.defaultConfig);
                }
            }
        },
        'definition.config'() {
            if (this.config === undefined) {
                return;
            }
            try {
                Object.assign(this.config, this.defaultConfig, JSON.parse(this.definition.config));
            } catch (e) {
                Object.assign(this.config, this.defaultConfig);
            }
        },
        config: {
            deep: true,
            immediate: true,
            handler(config) {
                if (this.config === undefined) {
                    return;
                }
                if (this.defaultConfig === false) {
                    this.defaultConfig = config;
                } else {
                    const json = JSON.stringify(config);
                    json !== this.definition.config ? this.definition.config = json : null;
                }
            }
        }
    },
    data() {
        return {
            defaultConfig: false,
        };
    },
}
