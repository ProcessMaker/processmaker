import FormText from "@processmaker/vue-form-builder/src/components/renderer/form-text";

let initialControls =  [
    {
        builderComponent: FormText,
        builderBinding: 'FormText',
        rendererComponent: FormText,
        rendererBinding: 'FormText',
        control: {
            label: 'Text',
            component: 'FormText',
            'editor-component': 'FormText',
            'editor-icon': require('@processmaker/vue-form-builder/src/assets/icons/Label.png'),
            config: {
                label: 'New Text',
                fontSize: '1em',
                fontWeight: 'normal'
            },
            inspector: [
                {
                    type: "FormInput",
                    field: "label",
                    config: {
                        label: "Text Label",
                        helper: "The text to display",
                    }
                },
                {
                    type: "FormSelect",
                    field: "fontWeight",
                    config: {
                        label: "Font Weight",
                        helper: "The weight of the text",
                        options: [
                            {
                                value: 'normal',
                                content: 'Normal'
                            },
                            {
                                value: 'bold',
                                content: 'Bold'
                            }
                        ]
                    }
                },


                {
                    type: "FormSelect",
                    field: "fontSize",
                    config: {
                        label: "Font Size",
                        helper: "The size of the text in em",
                        options: [
                            {
                                value: '0.5em',
                                content: '0.5'
                            },
                            {
                                value: '1em',
                                content: '1'
                            },
                            {
                                value: '1.5em',
                                content: '1.5'
                            },
                            {
                                value: '2em',
                                content: '2'
                            },
                        ]
                    }
                },


            ]
        }
    },
]


ProcessMaker.EventBus.$on('screen-builder-init', (manager) => {
    for (var i = 0; i < initialControls.length; i++) {
        manager.addControl(
            initialControls[i].control,
            initialControls[i].rendererComponent,
            initialControls[i].rendererBinding,
            initialControls[i].builderComponent,
            initialControls[i].builderBinding
        );
    }
});
