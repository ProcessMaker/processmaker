<!-- data edit -->
<monaco-editor ref="monaco" :options="monacoLargeOptions" v-model="jsonData"
    language="json" style="border:1px solid gray; min-height:400px"></monaco-editor>

<div class="text-right mt-3">
    <button type="button" class="btn btn-secondary ml-2" @click="updateRequestData()">
        {{__('Save')}}
    </button>
</div>
