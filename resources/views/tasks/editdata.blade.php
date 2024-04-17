<!-- data edit -->
<monaco-editor v-show="!showTree" ref="monaco" data-cy="editorViewFrame" :options="monacoLargeOptions" v-model="jsonData"
               language="json" style="border:1px solid gray; min-height:700px;"></monaco-editor>

<tree-view v-if="showTree" v-model="jsonData" style="border:1px; solid gray; min-height:700px;"></tree-view>

<div class="d-flex justify-content-between mt-3">
    <data-tree-toggle v-model="showTree"></data-tree-toggle>
    <span>
        @isset($dataActionsAddons)
            @foreach ($dataActionsAddons as $dataActionsAddon)
                {!! $dataActionsAddon['content'] ?? '' !!}
            @endforeach
        @endisset
        <button type="button" class="btn btn-secondary" @click="updateRequestData()">
            {{__('Save')}}
        </button>
    </span>
</div>
