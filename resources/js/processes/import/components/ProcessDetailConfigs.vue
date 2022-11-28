<template>
 <div class="container mb-3" id="importProcess" v-cloak>
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header bg-light" align="left">
                        <h5 class="mb-0">{{$t('Configure Process Details')}}</h5>
                        <small class="text-muted">{{ $t('The process you are importing already exists. Please create a new unique name.') }}</small>
                    </div>
                    <div class="card-body">
                        <required></required>
                        <b-form-group
                        required
                        :label="$t('Name')"
                        :description="formDescription('The process name must be unique', 'name', addError)"
                        :invalid-feedback="errorMessage('name', addError)"
                        :state="errorState('name', addError)"
                        >
                            <b-form-input
                                autofocus
                                v-model="name"
                                autocomplete="off"
                                :state="errorState('name', addError)"
                                name="name"
                                required
                            ></b-form-input>
                        </b-form-group>

                        <b-form-group
                        required
                        :label="$t('Description')"
                        :invalid-feedback="errorMessage('description', addError)"
                        :state="errorState('description', addError)"
                        >
                            <b-form-textarea
                                required
                                v-model="description"
                                autocomplete="off"
                                rows="3"
                                :state="errorState('description', addError)"
                                name="description"
                            ></b-form-textarea>
                        </b-form-group>

                        <category-select :label="$t('Category')" api-get="process_categories"
                        api-list="process_categories" v-model="process_category_id"
                        :errors="addError.process_category_id"
                        name="category"
                        ></category-select>
                    </div>
                    <div class="card-footer bg-light" align="right">
                        <button type="button" class="btn btn-outline-secondary" @click="onCancel">
                            {{$t('Cancel')}}
                        </button>
                        <button type="button" class="btn btn-primary ml-2" @click="onImport">
                            {{$t('Import')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { FormErrorsMixin, Required } from "SharedComponents";
export default {
    props: ['file'],
    components: {Required},
    mixins: [FormErrorsMixin],
    data() {
        return{
            name: '',
            description: '',
            process_category_id: '',
            addError: {},
        }
    },
    methods:{
        onCancel() {
            window.location = '/processes';
        },
        onImport() {
            // TODO: UPDATE AND IMPORT FILE
            // let formData = new FormData();
            // formData.append('name', this.name);
            // formData.append('description', this.description);
            // formData.append('process_category_id', this.process_category_id);

            // ProcessMaker.apiClient
            // .post('update-process', formData)
            // .then(response => {
            //     this.processes = [];
            //     response.data.data.forEach(item => {
            //     item.events.forEach(start => {
            //         this.processes.push({
            //         'id': `${start.ownerProcessId}-${item.id}`,
            //         'name': item.events.length > 1 ? `${item.name} (${start.ownerProcessId})` : item.name,
            //         });
            //     });
            //     });
            // });
            console.log('IMPORT FILE', this.name, this.description, this.process_category_id, this.file);
          
        }
    },
    mounted() {
        console.log('MOUNT file', this.file);
    }
}
</script>

<style>

</style>