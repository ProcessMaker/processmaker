<template>
    <div>
        <b-form-group
            :label="$t('Name')"
            label-for="name-text"
            :description="$t('The template name must be unique.')"
            class="mb-3"
            :state="errorState('name', errors)"
            :invalid-feedback="errorMessage('name', errors)"
            required
        >
            <b-form-input v-model="template.name" id="name-text"></b-form-input>
        </b-form-group>

        <b-form-group
            :label="$t('Description')"
            label-for="description-text"
            class="mb-3"
            :state="errorState('description', errors)"
            :invalid-feedback="errorMessage('description', errors)"
            required
        >
            <b-form-textarea v-model="template.description" id="description-text"></b-form-textarea>
        </b-form-group>

        <b-form-group
            :label="$t('Version')"
            label-for="version-text"
            class="mb-3"
            :state="errorState('version', errors)"
            :invalid-feedback="errorMessage('version', errors)"
            required
        >
            <b-form-input v-model="template.version" id="version-text"></b-form-input>
        </b-form-group>

        <category-select :label="$t('Category')" api-get="process_categories"
            api-list="process_categories" v-model="template.process_category_id"
            :errors="errors.category"
        >
        </category-select>
    </div>
</template>

<script>
import FormErrorsMixin from "../../components/shared/FormErrorsMixin.js";
import CategorySelect from "../../components/shared/CategorySelect.vue";

export default {
    components: {CategorySelect},
    mixins: [FormErrorsMixin],
    props: ['templateData', 'permission', 'responseErrors'],
    data() {
        return {
            template: this.templateData,
            errors: {},
        }
    },
    watch: {
        template: {
            deep: true,
            handler() {
                this.$emit('updated', this.template);
            }
        },
        responseErrors: {
            deep: true,
            handler() {
                this.errors = this.responseErrors;
            }
        }
    },
}
</script>