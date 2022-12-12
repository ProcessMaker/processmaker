<template>
    <div class="mt-4 mb-5 data-card-container high-elevation">
        <b-card>
            <template #header>
                <div>
                    <i class="text-secondary data-card-header fas nav-icon fa-code" />
                    <h5 class="mb-0 data-card-header">Scripts</h5>
                        <b-form-checkbox
                        class="data-card-header export-all"
                        v-model="exportAllScripts"
                    >
                        Export All
                        </b-form-checkbox>
                </div>
            </template>
            <b-card-text>
                <ul class="data-card-metadata">
                    <li>Status: <span class="badge badge-success">Full Export</span></li>
                    <li>Total Elements: 18 Scripts</li>
                </ul>
                <div>
                    <b-link>
                        <i class="fas fa-info-circle fa-fw mr-0"></i>
                        Details
                    </b-link>
                </div>
            </b-card-text>
        </b-card>
    </div>
</template>

<script>
export default {
    props: [],
    components: {},
    mixins: [],
    data() {
        return {

        }
    },
    methods: {      
    },
    mounted() {
    }
}
</script>

<style>

.high-elevation {
    box-shadow: 0px 8px 10px 1px rgb(0 0 0 / 14%), 0px 3px 14px 2px rgb(0 0 0 / 12%), 0px 5px 5px -3px rgb(0 0 0 / 20%);
}

.low-elevation {
    box-shadow: 0px 1px 2px 0px rgb(60 64 67 / 25%), 0px 2px 6px 2px rgb(60 64 67 / 10%);
}

#header {
    display: flex;
}

.data-card-header {
    display: inline;
}

.data-card-metadata {
    padding-left: 0;
}

.export-all {
    justify-content: flex-end;
}

.badge {
    border-radius: 1em;
    padding-left: 0.5em;
    padding-right: 0.5em;
}
</style>

