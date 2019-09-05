
import component from './component.vue';
import Configuration from "./inspector/Configuration";
import DataMapping from "./inspector/DataMapping";

const implementation = 'package-data-sources/datasource-task-service';
const nodeId = 'datasource-task-service';

export default {
    // Component properties
    id: nodeId,
    component: component,
    bpmnType: 'bpmn:ServiceTask',
    control: true,
    category: 'Datasources',
    icon: require('./icon.svg'),
    implementation,
    label: 'Data Source',
    /**
     * BPMN definition
     */
    definition: function (moddle) {
        return moddle.create('bpmn:ServiceTask', {
            name: 'Data source',
            implementation,
            config: JSON.stringify({ dataSource: "", dataMapping: [] }),
        });
    },
    /**
     * BPMN definition (diagram)
     */
    diagram: function (moddle) {
        return moddle.create('bpmndi:BPMNShape', {
            bounds: moddle.create('dc:Bounds', {
                height: 76,
                width: 116,
            }),
        });
    },
    /**
     * Inspector handler
     */
    inspectorHandler: function (value, definition, component) {
        // Go through each property and rebind it to our data
        for (var key in value) {
            // Only change if the value is different
            if (definition[key] != value[key]) {
                definition[key] = key === 'config' ? JSON.stringify(value[key]) : value[key];
            }
        }
        component.updateShape();
    },
    /**
     * Inspector definition
     */
    inspectorConfig: [
        {
            name: 'datasource-task-service',
            items: [
                {
                    component: 'FormAccordion',
                    container: true,
                    config: {
                        initiallyOpen: true,
                        label: 'Configuration',
                        icon: 'cog',
                        name: 'configuration',
                    },
                    items: [{
                        component: Configuration,
                        config: {
                            name: 'id',
                        },
                    }],
                },
                {
                    component: 'FormAccordion',
                    container: true,
                    config: {
                        initiallyOpen: true,
                        label: 'Data mapping',
                        icon: 'table',
                        name: 'data_mapping',
                    },
                    items: [{
                        component: DataMapping,
                        config: {
                            name: 'id',
                        },
                    }],
                },
            ]
        }
    ],
};
