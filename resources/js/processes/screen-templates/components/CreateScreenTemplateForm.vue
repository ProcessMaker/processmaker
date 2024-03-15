<template>
    <div>
        <b-tabs class="screen-templates-form" content-class="p-4">
            <b-tab :title="$t('Template Settings')" active>
                <b-row>
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
                            :label="$t('Type')"
                            :state="errorState('type', errors)"
                            :invalid-feedback="errorMessage('type', errors)"
                        >
                            <screen-type-dropdown
                                :value="screenType"
                                :screen-types="types"
                                copy-asset-mode="true"
                                hideDescription="true"
                            />
                        </b-form-group>

                        <category-select
                            v-model="templateData.screen_category_id"
                            :label="$t('Category')"
                            api-get="screen_categories"
                            api-list="screen_categories"
                            name="category"
                            :invalid-feedback="errorMessage('screen_category_id', errors)"
                            :state="errorState('screen_category_id', errors)"
                        />
                    </b-col>
                    <b-col>
                        <multi-thumbnail-file-uploader 
                            :label="$t('Template Thumbnail')" 
                            class="mb-3"
                            @input="handleThumbnails"
                        />

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

                        <b-form-group v-if="canMakePublicTemplates">
                            <b-form-checkbox
                                id="make-screen-template-public"
                                v-model="templateData.is_public"
                                name="make-screen-template-public"
                                value="true"
                                unchecked-value="false"
                            >
                            {{ $t('Make Public') }}
                            </b-form-checkbox>
                        </b-form-group>
                    </b-col>
                </b-row>
            </b-tab>
        </b-tabs>
    </div>
</template>

<script>
import Required from "../../../components/shared/Required.vue";
import FormErrorsMixin from "../../../components/shared/FormErrorsMixin";
import MultiThumbnailFileUploader from '../../../components/shared/MultiThumbnailFileUploader'
import ScreenTypeDropdown from "../../screens/components/ScreenTypeDropdown.vue";

export default {
    components: {MultiThumbnailFileUploader, ScreenTypeDropdown },
    mixins: [Required, FormErrorsMixin ],
    props: ["screenType", "permission", "types", "responseErrors"],
    data() {
        return {
            errors: {},
            templateData: {
                name: "",
                description: "",
                is_public: false,
                media_collection: '',
                thumbnails: "[]",
                type: "",
                version: null,
                unique_template_id: "",
                screen_category_id: null
            },
        }
    },
    computed: {
        canMakePublicTemplates() {
            return this.permission.includes('publish-screen-templates');
        }

    },
    watch: {
        templateData: {
            deep: true,
            handler() {
                if (this.templateData.name.length > 255) {
                    this.errors.name = ['Name must be less than 255 characters.'];
                } else {
                    this.errors.name = null;
                }
                this.$emit("input", this.templateData, this.errors);
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
            this.templateData.thumbnails = JSON.stringify(images);
        }
    },
    mounted() {
        this.templateData.screenType = this.screenType;
    }
}
</script>

<style type="scss">
.screen-templates-form {
    .nav-tabs {
        border: none !important;
        .nav-link {
            font-weight:bold;
            padding: 15px 20px;
            border-bottom: 0!important;
            &.active {
                box-shadow: none !important;
            }
        }
        
    }

    .tab-content {
        border: 1px solid #CDDDEE;
    }

    .image-thumbnails-container {
        margin:0;
        max-height: 270px;
    }
}   
</style>
