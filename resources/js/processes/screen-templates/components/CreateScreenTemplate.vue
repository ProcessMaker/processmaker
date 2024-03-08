<template>
    <div>
        <b-tabs content-class="mt-3">
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
                        v-model="name"
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
                        v-model="description"
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
                    <b-form-select
                        required
                        v-model="screenType"
                        :options="screenTypes"
                        name="type"
                        disabled
                    >
                    </b-form-select>
                    </b-form-group>
                  </b-col>
                  <b-col>
                    <multi-thumbnail-file-uploader label="Template Thumbnails"></multi-thumbnail-file-uploader>
                  </b-col>
                </b-row>
                <!-- <b-form-group
                required
                :label="$t('Template Name')"
                :description="formDescription('The template name must be unique.', 'name', errors)"
                :invalid-feedback="errorMessage('name', errors)"
                :state="errorState('name', errors)"
                >
                <b-form-input
                    required
                    autofocus
                    v-model="name"
                    autocomplete="off"
                    :state="errorState('name', errors)"
                    name="name"
                ></b-form-input>
                </b-form-group> -->
            </b-tab>
        </b-tabs>
    </div>
</template>

<script>
import Required from "../../../components/shared/Required.vue";
import FormErrorsMixin from "../../../components/shared/FormErrorsMixin";
import MultiThumbnailFileUploader from '../../../components/shared/MultiThumbnailFileUploader'

export default {
    components: {MultiThumbnailFileUploader },
    mixins: [Required, FormErrorsMixin ],
    props: ["screenType"],
    data() {
        return {
            errors: {},
            name: "",
            description: "",
            // TODO: Dynamically set the screenTypes array
            screenTypes: [
                {value: "FORM", text: "Form"},
                {value: "DISPLAY", text: "Display"},
                {value: "EMAIL", text: "Email"},
                {value: "CONVERSATIONAL", text: "Conversational"},
            ]
        }
    }
}
</script>
