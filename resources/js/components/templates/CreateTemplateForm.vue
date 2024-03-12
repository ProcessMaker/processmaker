<template v-else>
    <b-row align-v="start">
        <b-col>
            <b-form-group
                required
                :label="$t('Template Name')"
                :description="formDescription('The template name must be unique.', 'name', errors)"
                :invalid-feedback="errorMessage('name', errors)"
                :state="errorState('name', errors)"
            >
                <b-form-input
                required
                autofocus
                v-model="templateData.name"
                autocomplete="off"
                :state="errorState('name', errors)"
                name="name"
                ></b-form-input>
            </b-form-group>

            <b-form-group
                required
                :label="$t('Description')"
                :invalid-feedback="errorMessage('description', errors)"
                :state="errorState('description', errors)"
            >
                <b-form-textarea
                required
                v-model="templateData.description"
                autocomplete="off"
                rows="3"
                :state="errorState('description', errors)"
                name="description"
                ></b-form-textarea>
            </b-form-group>

            <b-form-group
                required
                :label="$t('Version')"
                :invalid-feedback="errorMessage('version', errors)"
                :state="errorState('version', errors)"
            >
                <b-form-input
                required
                autofocus
                v-model="templateData.version"
                autocomplete="off"
                :state="errorState('version', errors)"
                name="version"
                ></b-form-input>
            </b-form-group>

            <category-select
                v-model="templateData.process_category_id"
                :label="$t('Category')"
                api-get="process_categories"
                api-list="process_categories"
                name="category"
                :errors="addError.process_category_id"
            />

            <b-form-group>
                <b-form-radio 
                    v-model="templateData.saveAssetsMode" 
                    name="save-mode-options" 
                    value="saveAllAssets">{{ $t('Save all assets') }}
                </b-form-radio>

                <b-form-radio 
                    v-model="templateData.saveAssetsMode" 
                    name="save-mode-options" 
                    value="saveModelOnly">{{ $t(`Save ${assetType} model only`) }}
                </b-form-radio>
            </b-form-group>
        </b-col>
    </b-row>
</template>

<script>
import FormErrorsMixin from "../shared/FormErrorsMixin";

export default {
    components: {},
    mixins: [FormErrorsMixin],
    props: ["responseErrors", "assetType"],
    data() {
        return {
            errors: {},
            addError: {},
            templateData: {
                name: "",
                description: "",
                version: null,
                process_category_id: "",
                saveAssetsMode: "saveAllAssets",
            },
        }
    },
    watch: {
        templateData: {
            deep: true,
            handler() {
                this.$emit("input", this.templateData);
            }
        },
        responseErrors: {
            deep: true,
            handler() {
                this.errors = this.responseErrors;
            }
        }
    }
}
</script>