<template>
    <div class="form-group">
        <label>{{label}}</label>
        <div v-if="loading">
            Loading...
        </div>
        <div v-else>
            <select class="form-control" @change="updateValue">
                <option value=""></option>
                <option :value="screen.id" :selected="screen.id == value" v-for="screen in screens" :key="screen.id">
                    {{screen.title}}
                </option>
            </select>
            <a href="#" @click="load">Refresh</a>
        </div>
    </div>
</template>


<script>
export default {
    props: ["value", "label", "helper"],
    data() {
        return {
            content: "",
            loading: true,
            screens: null
        };
    },
    computed: {
        node() {
            return this.$parent.$parent.inspectorNode;
        }
    },
    mounted() {
        // Check to see if we already have a value set, if not, set it to first option
        // Also check if we have at least one option available
        if (!this.value && this.screens) {
            this.content = this.screens[0].id;
            this.$emit("input", this.content);
        }
        this.load();
    },
    watch: {
        currentValue: {
            handler() {
                this.$emit("change", this.currentValue);
            }
        }
    },
    methods: {
        updateValue(e) {
            this.content = e.target.value;
            this.$emit("input", this.content);
        },
        load() {
            this.loading = true;
            ProcessMaker.apiClient
                .get("/scripts")
                .then(response => {
                    this.screens = response.data.data;
                    this.loading = false;
                })
                .catch(err => {
                    this.loading = false;
                });
        }
    }
};
</script>

<style lang="scss" scoped>
</style>