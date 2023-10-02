<template>
    <div>
        <div v-if="hasAuthorizedBadge">
            <b-badge pill :variant="setting.ui.authorizedBadge ? 'success' : 'warning'">
                <span v-if="setting.ui.authorizedBadge">{{ $t('Authorized') }}</span>
                <span v-else>{{ $t('Not Authorized') }}</span>
            </b-badge>
        </div>
        <div v-else>
            Empty
        </div>
        <b-modal class="setting-object-modal" v-model="showModal" size="lg" @hidden="onModalHidden" centered>
            <template v-slot:modal-header class="d-block">
                <div>
                <h5 class="mb-0" v-if="setting.name">{{ $t(setting.name) }}</h5>
                <h5 class="mb-0" v-else>{{ setting.key }}</h5>
                <small class="form-text text-muted">{{ $t('Configure the driver connection properties.') }}</small>
                </div>
                <button type="button" :aria-label="$t('Close')" class="close" @click="onCancel">×</button>
            </template>
            <div>
                <b-form-group
                    required
                    :label="$t('Client ID')"
                    :description="formDescription('The client ID assigned when you register your application.', 'client_id', errors)"
                    :invalid-feedback="errorMessage('client_id', errors)"
                    :state="errorState('client_id', errors)"
                >
                    <b-form-input
                        required
                        autofocus
                        v-model="formData.client_id"
                        autocomplete="off"
                        :state="errorState('client_id', errors)"
                        name="client_id"
                    ></b-form-input>
                </b-form-group>

                <b-form-group
                    required
                    :label="$t('Client Secret')"
                    :description="formDescription('The client secret assigned when you register your application.', 'client_secret', errors)"
                    :invalid-feedback="errorMessage('client_secret', errors)"
                    :state="errorState('client_secret', errors)"
                >
                    <b-input-group>
                        <b-form-input
                            required
                            autofocus
                            v-model="formData.client_secret"
                            autocomplete="off"
                            trim
                            :type="type"
                            :state="errorState('client_secret', errors)"
                            name="client_secret"
                        ></b-form-input>
                        <b-input-group-append>
                            <b-button :aria-label="$t('Toggle Show Password')" variant="secondary" @click="togglePassword">
                                <i class="fas" :class="icon"></i>
                            </b-button>
                        </b-input-group-append>
                    </b-input-group>
                </b-form-group>

                <b-form-group
                    required
                    :label="$t('Redirect URL')"
                    :description="formDescription('This value must match the callback URL you specify in your app settings.', 'callback_url', errors)"
                    :invalid-feedback="errorMessage('callback_url', errors)"
                    :state="errorState('callback_url', errors)"
                >
                    <b-input-group>
                        <b-form-input
                            autofocus
                            v-model="formData.callback_url"
                            readonly
                            autocomplete="off"
                            :state="errorState('callback_url', errors)"
                            name="callback_url"
                        ></b-form-input>
                        <b-input-group-append>
                            <b-button :aria-label="$t('Copy')" variant="secondary" @click="onCopy">
                                <i class="fas fa-copy"></i>
                            </b-button>
                        </b-input-group-append>
                    </b-input-group>
                </b-form-group>

                <additional-driver-connection-properties :driverKey="setting.key" :formData="formData" @updateFormData="updateFormData"></additional-driver-connection-properties>  
            </div>
            <div slot="modal-footer" class="w-100 m-0 d-flex">
                <button type="button" class="btn btn-outline-secondary ml-auto" @click="onCancel">
                    {{ $t('Cancel') }}
                </button>
                <button type="button" class="btn btn-secondary ml-3" @click="onSave" :disabled=" isInvalid || !changed ">
                    {{ $t('Authorize')}}
                </button>
            </div>
        </b-modal>

        <b-modal class="setting-object-modal" v-model="showAuthorizingModal" size="lg" hide-footer hide-header centered no-fade>
            <div class="text-center">
                <h3>{{ $t('Connecting Driver') }}</h3>
                <i class="fas fa-circle-notch fa-spin"></i>
            </div>
        </b-modal>
    </div>
</template>

<script>
import settingMixin from "../mixins/setting";
import { FormErrorsMixin,Required } from "SharedComponents";
import AdditionalDriverConnectionProperties from "./AdditionalDriverConnectionProperties.vue";

export default {
    mixins: [settingMixin, FormErrorsMixin, Required],
    props: ['setting', 'value'],
    components: {AdditionalDriverConnectionProperties},
    data() {
        return {
            input: '',
            formData: {
                client_id: "",
                client_secret: "",
                callback_url: "",
            },
            selected: null,
            showModal: false,
            showAuthorizingModal: false,
            transformed: null,
            errors: {},
            isInvalid: true,
            type: 'password',
            resetData: true,
        }
    },
    computed: {
        hasAuthorizedBadge() {
            if (!this.setting) {
                return false;
            }
            const hasAuthorizedBadge = _.has(this.setting, 'ui.authorizedBadge') ? true : false;
            return hasAuthorizedBadge;
        },
        changed() {
            return JSON.stringify(this.formData) !== JSON.stringify(this.transformed)
        },
        icon() {
            if (this.type == 'password') {
                return 'fa-eye';
            } else {
                return 'fa-eye-slash';
            }
        },
    },
    watch: {
        formData: {
            handler() {
                this.isInvalid = this.validateData();
            },
            deep: true,
        }
    },
    methods: {
        onCopy() {
            navigator.clipboard.writeText(this.formData.callback_url).then(() => {
                ProcessMaker.alert(this.$t("The setting was copied to your clipboard."), "success");
            }, () => {
                ProcessMaker.alert(this.$t("The setting was not copied to your clipboard."), "danger");
            });
        },
        togglePassword() {
            if (this.type == 'text') {
                this.type = 'password';
            } else {
                this.type = 'text';
            }
        },
        validateData() {
            return _.isEmpty(this.formData) || _.some(this.formData, _.isEmpty);
        },
        onCancel() {
            this.showModal = false;
        },
        onEdit(row) {
            if (this.value !== null) {
                this.formData = this.value;
            }
            this.generateCallbackUrl(row.item);
            this.$nextTick(() => {
                this.showModal = true;
                
            });
        },
        onModalHidden() {
            this.resetFormData();
        },
        authorizeConnection() {
            this.showAuthorizingModal = true;
            this.showModal = false;
            this.resetData = false;
            ProcessMaker.apiClient.post(`settings/${this.setting.id}/get-oauth-url`)
            .then(response => {
                ProcessMaker.alert('successfully authorized', 'success');
                this.setting.ui.authorizedBadge = true;
                this.emitSaved(this.setting);
                this.showAuthorizingModal = false;
            })
            .catch(error => {
                ProcessMaker.alert(error.message, 'danger');
                this.showModal = true;
                this.showAuthorizingModal = false;
            });
        },
        onSave() {
            const driver = this.setting.key.split('cdata.')[1];
            const dsn = `CData ${this.setting.name} Source`;

            this.formData.driver = driver;
            this.formData.dsn = dsn;

            this.emitSaved(this.formData);
            
            this.transformed = { ...this.formData };

            this.$nextTick(() => {
                this.authorizeConnection();
            });
        },
        generateCallbackUrl(data) {
            const name = data.key.split('cdata.')[1];
            const app_url = document.head.querySelector('meta[name="app-url"]').content;
            
            this.formData.callback_url = `${app_url}/external-integrations/${name}`;
        },
        resetFormData() {
            if (this.resetData) {
                this.formData = {
                    client_id: "",
                    client_secret: "",
                    callback_url: "",
                };
            }
        },
        updateFormData(val) {
            this.formData = {...this.formData, ...val};
        }
    },
    mounted() {
        if (this.value === null) {
            this.resetFormData();
        } else {
            this.formData = this.value;
        }
        this.isInvalid = this.validateData();
        this.transformed = this.copy(this.formData);
    } 
}
</script>