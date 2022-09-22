<template>
  <div>
    <modal 
      id="set-password-modal" 
      :title="$t('Set Password')" 
      :subtitle="$t('This password will be required when importing the exported package/process.')"
      :ok-disabled="disabled" 
      @ok.prevent="onSubmit" 
      @hidden="onClose" 
      :ok-title="$t('Export')"
    >
      <!-- <div class="card-header bg-light pt-0 pl-0">
        <!-- <h6 class="text-muted pt-0">{{ $t("This password will be required when importing the exported package/process.") }}</h6> -->
      <!-- </div> -->
      <div>
        <b-form-checkbox class="pt-3" v-model="passwordProtect" switch>
          Password Protect Export
        </b-form-checkbox>
      </div>
        <template v-if="passwordProtect === true">
          <div class="pt-3">
            <label for="set-password">Password</label>
            <b-input-group>
              <b-form-input id="set-password" type="password" v-model="password"></b-form-input>
              <b-input-group-append>
                <b-button :class="icon" class="fas" :aria-label="$t('Toggle Show Password')" variant="outline-secondary" @click="togglePassword"></b-button>
              </b-input-group-append>
            </b-input-group>
          </div>
          <div class="pt-3">
            <label for="confirm-set-password">Verify Password</label>
            <b-input-group>
              <b-form-input id="confirm-set-password" type="password"></b-form-input>
              <b-input-group-append>
                <b-button :class="icon" class="fas" :aria-label="$t('Toggle Show Password')" variant="outline-secondary" @click="togglePassword"></b-button>
              </b-input-group-append>
            </b-input-group>
          </div>
        </template>
        <template v-else>
          <div class="pt-3">
            <label for="set-password">Password</label>
            <b-input-group>
              <b-form-input id="set-password" type="password" disabled></b-form-input>
              <b-input-group-append>
                <b-button :class="icon" class="fas" :aria-label="$t('Toggle Show Password')" variant="outline-secondary" @click="togglePassword" disabled></b-button>
              </b-input-group-append>
            </b-input-group>
          </div>
          <div class="pt-3">
            <label for="confirm-set-password">Verify Password</label>
            <b-input-group>
              <b-form-input id="confirm-set-password" type="password" disabled></b-form-input>
              <b-input-group-append>
                <b-button :class="icon" class="fas" :aria-label="$t('Toggle Show Password')" variant="outline-secondary" @click="togglePassword" disabled></b-button>
              </b-input-group-append>
            </b-input-group>  
          </div>
        </template>
      <template #modal-footer>
      <div align="right">
        <button type="button" class="btn btn-outline-secondary" @click="onCancel">
          {{ $t("Cancel") }}
        </button>
        <button type="button" class="btn btn-primary ml-2" @click="onExport">
          {{ $t("Export") }}
        </button>
      </div>
      </template>
    </modal>
  </div>
</template>

<script>
import { Modal } from "SharedComponents";

export default {
  props: ["processId"],
    components: { Modal },
    mixins: [],
    data() {
        return {
          passwordProtect: true,
          disabled: false,
          password: ''
        }
    },
    computed: {
      icon() {
      if (this.type == 'password') {
        return 'fa-solid fa-eye';
      } else {
        return 'fa-solid fa-eye-slash';
      }
    },
    },
    methods: { 
      resetFormData() {
        this.formData = Object.assign({}, {
          title: null,
          type: '',
          description: null,
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
            window.location = '/processes/'+ this.processId + '/export';
        },
      onExport() {
            ProcessMaker.apiClient.post('processes/' + this.processId + '/export')
            .then(response => {
                window.location = response.data.url;
                ProcessMaker.alert(this.$t('The process was exported.'), 'success');
            })
            .catch(error => {
                ProcessMaker.alert(error.response.data.message, 'danger');
            });
        },
      togglePassword() {
            if (this.type == 'text') {
              this.type = 'password';
            } else {
              this.type = 'text';
            }
        },
        onSubmit () {
        this.errors = Object.assign({}, {
          name: null,
          description: null,
          process_category_id: null,
          status: null
        });
        if (this.process_category_id === "") {
          this.addError = {"process_category_id": ["{{__('The category field is required.')}}"]};
          return;
        }
        //single click
        if (this.disabled) {
          return;
        }
        this.disabled = true;

        let formData = new FormData();
        formData.append("name", this.name);
        formData.append("description", this.description);
        formData.append("process_category_id", this.process_category_id);
        if (this.file) {
          formData.append("file", this.file);
        }

        ProcessMaker.apiClient.post("/processes", formData,
          {
            headers: {
              "Content-Type": "multipart/form-data"
            }
          })
          .then(response => {
            ProcessMaker.alert(this.$t('The process was created.'), "success");
            window.location = "/modeler/" + response.data.id;
          })
          .catch(error => {
            this.disabled = false;
            this.addError = error.response.data.errors;
          });
      }
  },     
  mounted() {
  }

}
</script>

<style>

</style>