<!-- data edit -->
<div class="card card-body mt-3">
    <div class="form-group">
        <button type="button" class="btn btn-sm float-right btn-success" @click="updateRequestData()">
            <i class="fas fa-save"></i>
            {{__('Save')}}
        </button>
    </div>
    <monaco-editor :options="monacoLargeOptions" v-model="jsonData"
        language="json" style="height: 12em;border:1px solid gray;"></monaco-editor>
</div>
