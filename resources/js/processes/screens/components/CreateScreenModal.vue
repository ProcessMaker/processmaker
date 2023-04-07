<template>
  <div>
    <b-button :aria-label="$t('Create Screen')" v-b-modal.createScreen class="mb-3 mb-md-0 ml-md-2">
      <i class="fas fa-plus"></i> {{ $t('Screen') }}
    </b-button>
    <modal id="createScreen" :title="$t('Generate Form')" :ok-disabled="disabled" @ok.prevent="onSubmit" @hidden="onClose">
      <template v-if="countCategories">
        <!-- <required></required> -->
        <!-- <b-form-group
          required
          :label="$t('Name')"
          :description="formDescription('The screen name must be unique.', 'title', errors)"
          :invalid-feedback="errorMessage('title', errors)"
          :state="errorState('title', errors)"
        >
          <b-form-input
            required
            autofocus
            v-model="formData.title"
            autocomplete="off"
            :state="errorState('title', errors)"
            name="title"
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
            v-model="formData.description"
            autocomplete="off"
            rows="3"
            :state="errorState('description', errors)"
            name="description"
          ></b-form-textarea>
        </b-form-group> -->
        <b-form-group
          :label="$t('Describe your form')"
          :invalid-feedback="errorMessage('config', errors)"
          :state="errorState('config', errors)"
        >
          <b-form-textarea
            v-model="formData.config"
            placeholder="I want a form for . . ."
            autocomplete="off"
            rows="5"
            :state="errorState('config', errors)"
            name="config"
          ></b-form-textarea>
        </b-form-group>
        <b-row>
          <b-col>
            <b-button
              sm="4"
              class="custom-btn pb-2"
              variant="outline-success"
              @click="generateForm(formData.config)"
            >
              Generate Form
            </b-button>
            <b-button
              sm="4"
              class="custom-btn pb-2"
              variant="outline-danger"
              @click="reset()"
            >
              Reset
            </b-button>
          </b-col>
        </b-row>
        <b-row>
          <b-col md="12" >
            <TypeView :dataJson="dataJson" />
          </b-col>
        </b-row>  
          
      </template>
      <template v-else>
        <div>{{ $t('Categories are required to create a screen') }}</div>
        <a href="/designer/screens/categories" class="btn btn-primary container mt-2">
            {{ $t('Add Category') }}
        </a>
      </template>
    </modal>
  </div>
</template>

<script>
  import { FormErrorsMixin, Modal, Required } from "SharedComponents";
  import { Draft07, JSONSchema, JSONError } from "json-schema-library";
  import TypeView from "./typeView.vue";
  import axios from "axios";

  export default {
    components: { Modal, Required, TypeView },
    mixins: [ FormErrorsMixin ],
    props: ["countCategories", "types"],
    data() {
      return {
        formData: {},
        errors: {
          'title': null,
          'type': null,
          'description': null,
          'category': null,
        },
        disabledGptText: false,
        disabled: true,
        dataJson: null,
        messages: []
      }
    },
    mounted() {
      this.resetFormData();
      this.resetErrors();
    },
    watch:{
      dataJson() {
        this.disabled = this.dataJson === null;
      }
    },
    methods: {
      resetFormData() {
        this.formData = Object.assign({}, {
          title: 'test',
          type: 'FORM',
          description: "aaabbbccc",
          config: null,
          screen_category_id:"1"
        });
      },
      resetErrors() {
        this.errors = Object.assign({}, {
          title: null,
          type: null,
          description: null,
        });
      },
      onClose() {
        this.resetFormData();
        this.resetErrors();
      },

      fieldsFactory(schema, key) {
        let name = key.substring(key.lastIndexOf("/") + 1);
        if (schema && schema.type == "string" && schema.enum) {
          const optionList = [];
          if (schema.enum) {
            schema.enum.forEach(function(number) {
              optionList.push({
                value: number,
                content: number
              })
            })
          }

          fieldJSONBase.config.options = {
            "dataSource": "provideData",
            "jsonData": JSON.stringify(optionList),
            "dataName": "response",
            "key": "value",
            "value": "content",
            "pmqlQuery": "",
            "selectedOptions": [],
            "optionsList": optionList,
            "showRenderAs": true,
            "renderAs": "dropdown",
            "allowMultiSelect": false,
            "showOptionCard": false,
            "showRemoveWarning": false,
            "showJsonEditor": false,
            "editIndex": null,
            "removeIndex": null,
            "valueTypeReturned": "single"
          },
          
          fieldJSONBase.component = "FormSelectList";
          fieldJSONBase["editor-component"] = "FormSelectList";
          fieldJSONBase["editor-control"] = "FormSelectList";
        }
        return fieldJSONBase;
      },
    
      onSubmit() {
        
        this.resetErrors();
        const jsonBase = {
          "name": this.formData.type,
          "items": []
        };
        const jsonSchema = new Draft07(this.dataJson);
      
        const myCallback = (schema, key) => {
          if (key!== "#") {
            jsonBase.items.push(
              this.fieldsFactory(schema, key)
            );
          }
        };
        jsonSchema.eachSchema(myCallback);
         const payload =  {
          title: this.dataJson && this.dataJson.title ? this.dataJson.title+"_"+Math.round(Math.random()*100000000): "form_test_" + Math.round(Math.random()*100000000),
          type: this.formData.type,
          description: this.dataJson && this.dataJson.title ? this.dataJson.title: "description",
          screen_category_id: this.formData.screen_category_id,
          config: [jsonBase]
        };
        ProcessMaker.apiClient.post('screens', payload)
          .then(response => {
            ProcessMaker.alert(this.$t('The screen was created.'), 'success');
            window.location = '/designer/screen-builder/' + response.data.id + '/edit';
          })
          .catch(error => {
            if (error.response.status && error.response.status === 422) {
              this.errors = error.response.data.errors;
            }
          });
      },
      reset() {
        this.dataJson = null;
        this.formData.config = null;
      },
      generateForm(message) {
        ProcessMaker.apiClient.get('screens/openai', {
          params: {
            message: message
          },
          
        }, { timeout: 50000 })
        .then(response => {
          console.log(response.data);
          this.dataJson = response.data;
          if (typeof(this.dataJson) !== "object") {
            window.ProcessMaker.alert("Invalid JSON schema", "danger");
            this.dataJson = null;
          }
        });
      }
    }
  };
</script>
