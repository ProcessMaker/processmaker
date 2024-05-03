<template>
    <div class="row">
        <div class="col-6">
            <b-form-group
                :label="$t('Name')"
                label-for="name-text"
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
                required
                :label="$t('Type')"
                :state="errorState('type', errors)"
                :invalid-feedback="errorMessage('type', errors)"
            >
                <screen-type-dropdown
                    id="screenConfigsScreenType"
                    :value="template.screen_type"
                    :screen-types="screenTypes"
                    copy-asset-mode="true"
                    hideDescription="true"
                />
            </b-form-group>

            <category-select :label="$t('Category')" api-get="screen_categories"
                api-list="screen_categories" v-model="template.screen_category_id"
                :errors="errors.category"
            >
            </category-select>
        </div>
        <div class="col-6">
            <multi-thumbnail-file-uploader 
                :label="$t('Template Thumbnail')" 
                class="mb-3"
                modelType="template/screen"
                :modelId="template.id"
                :value="template.media"
                @input="handleThumbnails"
            />

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

            <b-form-group v-if="canMakePublicTemplates && !isSharedTemplate">
                <b-form-checkbox
                    id="make-screen-template-public"
                    v-model="isSharedTemplate"
                    name="make-screen-template-public"
                    :value="true"
                    :unchecked-value="false"
                >
                {{ $t('Share Template') }}
                </b-form-checkbox>
            </b-form-group>

        </div>
    </div>
</template>

<script>
import FormErrorsMixin from "../../components/shared/FormErrorsMixin.js";
import CategorySelect from "../../components/shared/CategorySelect.vue";
import MultiThumbnailFileUploader from '../../components/shared/MultiThumbnailFileUploader';
import ScreenTypeDropdown from "../../processes/screens/components/ScreenTypeDropdown.vue";

export default {
    components: {CategorySelect, MultiThumbnailFileUploader, ScreenTypeDropdown},
    mixins: [FormErrorsMixin],
    props: ['templateData', 'permission', 'screenTypes', 'responseErrors'],
    data() {
        return {
            template: this.templateData,
            errors: {},
        }
    },
    computed: {
        canMakePublicTemplates() {
            return this.permission.includes('publish-screen-templates');
        },
        isSharedTemplate: {
          get() {
              return this.template.is_public === 1;
          },
          set(value) {
              this.template.is_public = value;
          },
        },
        isDefaultProcessmakerTemplate() {
            return this.template.user_id === null;
        },
    },
    watch: {
        template: {
            deep: true,
            handler() {
                this.$emit('updated', this.template, this.isDefaultProcessmakerTemplate);
            }
        },
        responseErrors: {
            deep: true,
            handler() {
                this.errors = this.responseErrors;
            }
        }

    },
    methods: {
        handleThumbnails(images) {
            this.template.template_media = images;
        },
    }
}
</script>