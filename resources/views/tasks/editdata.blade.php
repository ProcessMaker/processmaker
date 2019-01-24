<table class="vuetable table table-hover">
    <thead>
        <tr>
            <th scope="col">{{ __('Key') }}</th>
            <th scope="col">
                {{ __('Value') }}
                <button type="button" class="btn btn-sm float-right btn-success" @click="updateRequestData()">
                    <i class="fas fa-save"></i>
                    {{__('Save')}}
                </button>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr v-for="(value, name) in data">
            <td>@{{name}}</td>
            <td v-if="value instanceof Object">
                <div class="form-control">
                    <a href="javascript:void(0)" @click="editJsonData(name)">{...}</a>
                </div>
            </td>
            <td v-else><input :value="data[name]" @input="updateData(name, $event.target.value)" class="form-control" :class="{'border-warning': fieldsToUpdate.indexOf(name)>-1}"></td>
        </tr>
    </tbody>
</table>

<b-modal v-model="showJSONEditor" size="lg" centered :title="selectedData" v-cloak>
    <div class="editor-container">
        <monaco-editor :options="monacoLargeOptions" v-model="jsonData"
            language="json" style="height: 12em;border:1px solid gray;"></monaco-editor>
    </div>
    <div slot="modal-footer">
        <b-button @click="saveJsonData" class="btn btn-outline-secondary btn-sm text-uppercase">
            UPDATE
        </b-button>
        <b-button @click="closeJsonData" class="btn btn-secondary btn-sm text-uppercase">
            CLOSE
        </b-button>
    </div>

</b-modal>
