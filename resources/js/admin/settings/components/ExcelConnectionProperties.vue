<template>
    <div>
        <b-form-group
            :label="$t('Connection Type')"
            :description="formDescription('Specifies the file storage service, server, or file access protocol through which your Microsoft Excel files are stored and retreived.', 'connection_type', errors)"
            :invalid-feedback="errorMessage('connection_type', errors)"
            :state="errorState('connection_type', errors)"
        >
            <b-form-input
                autofocus
                v-model="config.connection_type"
                autocomplete="off"
                :state="errorState('connection_type', errors)"
                name="connection_type"
            ></b-form-input>
        </b-form-group>

        <b-form-group
            :label="$t('URI')"
            :description="formDescription('The Uniform Resource Identifier (URI) for the Excel resource location.', 'uri', errors)"
            :invalid-feedback="errorMessage('uri', errors)"
            :state="errorState('uri', errors)"
        >
            <b-form-input
                autofocus
                v-model="config.uri"
                autocomplete="off"
                :state="errorState('uri', errors)"
                name="uri"
            ></b-form-input>
        </b-form-group>
    </div>

</template>

<script>
import { FormErrorsMixin } from "SharedComponents";

export default {
    mixins: [FormErrorsMixin],
    props: ['formData'],
    data() {
        return {
            config: {
                connection_type: '',
                uri: '',
            },
            errors: {},
        }
    },
    watch: {
        config: {
            handler() {
                this.$emit('updateFormData', this.config);
            },
            deep: true,
        }
    },
    mounted() {
        this.config.connection_type = this.formData?.connection_type;
        this.config.uri = this.formData?.uri;
    }
}
</script>