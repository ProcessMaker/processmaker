<template>
    <div class="mb-2">
        <!-- TODO: Set Up Process Component -->
        <h2>Export Process: {{ processName }}</h2>
        <hr>
        <div>
            <h4>Summary</h4>
            <ul class="process-summary">
                <li> Description: <span class="process-metadata">{{ processDescription }}</span></li>
                <li> Categories: <span class="process-metadata">{{ processCategory }}</span></li>
                <li> Process Manager: <span class="process-metadata"><b-link>{{ processManager }}</b-link></span></li>
                <li> Created: <span class="process-metadata">{{ processCreatedAt }}</span></li>
                <li> Last Modified: <span class="process-metadata">{{ processUpdatedAt }}</span> By: <span class="process-metadata"><b-link>{{ processUpdatedBy }}</b-link></span></li>
            </ul>
        </div>
        <div>
            <b-form-group>
                <b-form-checkbox
                    v-model="passwordProtect"
                    class="process-metadata"
                    stacked
                >
                Password Protect Export
                <b-form-text class="process-options-helper-text">Define a password to protect your export file.</b-form-text>
                </b-form-checkbox>
                <b-form-checkbox
                    v-model="exportAllElements"
                    class="process-metadata"
                    stacked
                >
                Export All Elements
                <b-form-text class="process-options-helper-text">Include all Process Elements in your export file.</b-form-text>
                </b-form-checkbox>
            </b-form-group>
        </div>
        <hr>
        <data-card :exportAll="exportAll" />
    </div>
</template>

<script>

import DataCard from "../../../../components/shared/DataCard.vue";

export default {
  props: ["processName",
    "processDescription",
    "processCategory",
    "processManager",
    "processCreatedAt",
    "processUpdatedAt",
    "processUpdatedBy",
    "exportAll"],
    components: {
        DataCard,
    },
    mixins: [],
    data() {
        return {
            passwordProtect: true,
            exportAllElements: true,
        }
    },
    methods: {      
    },
    mounted() {
        console.log(this.processUpdatedBy);
    },
    watch: {
      exportAllElements() {
        if (this.exportAllElements === true) {
            this.exportAll = true;
        }
      }
    },
}
</script>

<style>

.process-summary {
    padding-left: 0;
}

.process-metadata {
    font-weight: 600;
}

.process-options-helper-text {
    margin-top: 0;
    margin-bottom: 2px;
}

</style>

