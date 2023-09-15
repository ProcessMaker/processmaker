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
        <b-modal class="setting-object-modal" v-model="showModal" size="lg" @hidden="onModalHidden">
            <template v-slot:modal-header class="d-block">
                <div>
                <h5 class="mb-0" v-if="setting.name">{{ $t(setting.name) }}</h5>
                <h5 class="mb-0" v-else>{{ setting.key }}</h5>
                <small class="form-text text-muted" v-if="setting.helper">{{ $t(setting.helper) }}</small>
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
                    <b-form-input
                        required
                        autofocus
                        v-model="formData.client_secret"
                        autocomplete="off"
                        :state="errorState('client_secret', errors)"
                        name="client_secret"
                    ></b-form-input>
                </b-form-group>

                <b-form-group
                    required
                    :label="$t('Redirect URL')"
                    :description="formDescription('This value must match the callback URL you specify in your app settings.', 'callback_url', errors)"
                    :invalid-feedback="errorMessage('callback_url', errors)"
                    :state="errorState('callback_url', errors)"
                >
                    <b-form-input
                        required
                        autofocus
                        v-model="formData.callback_url"
                        autocomplete="off"
                        :state="errorState('callback_url', errors)"
                        name="callback_url"
                    ></b-form-input>
                </b-form-group>
                
            </div>
            <div slot="modal-footer" class="w-100 m-0 d-flex">
                <button type="button" class="btn btn-outline-secondary ml-auto" @click="onCancel">
                    {{ $t('Cancel') }}
                </button>
                <button type="button" class="btn btn-secondary ml-3" @click="onSave" :disabled="! changed">
                    {{ $t('Save')}}
                </button>
            </div>
        </b-modal>
    </div>
</template>

<script>
import settingMixin from "../mixins/setting";
import { FormErrorsMixin,Required } from "SharedComponents";

export default {
    mixins: [settingMixin, FormErrorsMixin, Required],
    props: ['setting', 'value'],
    components: {},
    data() {
        return {
            input: '',
            formData: {},
            selected: null,
            showModal: false,
            transformed: null,
            errors: {},
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
            return JSON.stringify(this.formData) !== JSON.stringify(this.transformed);
        },
    },
    created() {

        console.log("GET SETTING CONNECTION PROPERTIES", this.setting);
    },
    methods: {
        onCancel() {
            this.showModal = false;
        },
        onEdit() {
            this.showModal = true;
        },
        onModalHidden() {
            this.transformed = this.copy(this.formData);
        },
        onSave() {
            this.formData = this.copy(this.transformed);
            this.showModal = false;
            this.emitSaved(this.input);
        },
    },
    mounted() {
        if (this.value === null) {
            this.formData = {};
        } else {
            this.formData = this.value;
        }
        this.transformed = this.copy(this.formData);
    } 
}
</script>