<template>
    <ul class="process-element-metadata">
        <li v-for="(data, i) in findExistingData(newData)" :key="i" v-html="data"></li>
        <!-- <li v-html="filterExistingAssets(item)"></li> -->
        <!-- {{ filterExistingAssets(item) }} -->
        <!-- <li v-if="item.description">Description: <span class="fw-semibold">{{ item.description }}</span></li>
        <li>Categories: <span class="fw-semibold">{{ item.categories }}</span></li>
        <li v-for="(attribute, i) in item.extraAttributes" :key="i">
            {{ i[0].toUpperCase() + i.substring(1) }}: <span class="fw-semibold">{{ attribute }}</span>
        </li> -->
        <!-- <li>Language: <span class="process-metadata"></span></li> -->
        <!-- <li>Created Date: <span class="fw-semibold">{{ item.createdAt }}</span></li>
        <li>Last Modified Date: <span class="fw-semibold">{{ item.updatedAt }}</span></li> -->
    </ul>
</template>

<script>
import moment from "moment";

export default {
    props: ["existingData", 'newData'],
    components: {
    },
    mixins: [],
    data() {
        return {
            
        }
    },
    methods: {
        findExistingData(data) { 
            const foundExistingData = this.existingData.find(existing => {return data.uuid === existing.uuid});
            // Data fields that we are comparing and displaying.
            const dataFields = [
                {"value": "description", "content": 'Description'},
                // TODO: Look into why the categories are not in the existingData.existingAttributes object.
                // {"value": "categories", "content": 'Categories'}, 
                {"value": "created_at", "content": 'Created Date'},
                {"value": "extraAttributes", "content": []},
                {"value": "updated_at", "content": 'Last Modified Date'},
            ];

            if (!foundExistingData) {
                // No existing data found. Format the newData to display. 
                return this.generateChangeLog(dataFields, data, null, true);
            }

            const existingData = Object.entries(foundExistingData.existingAttributes);
           // let newFields = {};
            // Filter the incoming data for the properties we need are comparing, set in the dataFields variable.
            const newData = Object.entries(data).filter(([key, _]) => {
                const found = dataFields.find(field => {
                    return field.value === key;
                });
                if (found) {
                    return data[found.value];
                }
            }).reduce((obj, value) => {
                //obj[value[0]] = {};
                obj[value[0]] = value[1];
                return obj;
            }, {});
    
            // Compare newData with existingData for changes
           let listItems = [];
            for (const [key, value] of Object.entries(newData)) {
                // Find the newData within the existingData
                const foundExisting = existingData.find(field => {
                    return field[0] === key;
                });
 
                if (foundExisting) {
                    let newData = {};
                    let existingData = {};
                    let isEqual = true;
                
                    newData[key] = value;   
                 
                    if (foundExisting[0] === 'created_at' || foundExisting[0] === 'updated_at') {
                        const dateA = new Date(value + 'Z');
                        const dateB = new Date(foundExisting[1].replace('000000Z', '000Z'));
                        const newDate = this.readableTimestamp(Math.floor(dateA.getTime() / 1000));
                        const existingDate = this.readableTimestamp(Math.floor(dateB.getTime() / 1000));
                        
                        isEqual = _.isEqual(newDate, existingDate);
                        // Update displayed date to a readable timestamp;
                        existingData[foundExisting[0]] = existingDate;
                    } else {
                        existingData[foundExisting[0]] = foundExisting[1];
                        isEqual = _.isEqual(newData, existingData);
                      
                    }
                    const item = this.generateChangeLog(dataFields, newData, existingData, isEqual);
                    listItems.push(...item);
                }
            }
            return listItems;
        },
        readableTimestamp(timestamp) {
            return this.formatDate(timestamp).format("YYYY-MM-DD HH:mm:ss");
        },
        formatDate(unixTime) {
            return moment(unixTime * 1000).add(new Date().getTimezoneOffset() / 60);
        },
        generateChangeLog(fields, newData, existingData = null, isEqual) {
            let list = [];
            for (const [key, value] of Object.entries(newData)) {
                const field = fields.find(field => {
                    return field.value == key;
                });
                if (field) {
                    // let label;
                    // let value = null;
                    // let content;
                    const content = this.formatChangeLogData(field.content,  newData[field.value], existingData[key], isEqual);
                    // if (Array.isArray(newData[field.value])) {
                    //     value = newData[field.value].length > 0 ? `<span class="fw-semibold"> ${newData[field.value]}</span>` : null;
                    // } else {
                    //     value = !_.isEmpty(newData[field.value]) ? `<span class="fw-semibold">${newData[field.value]}</span>` : null;
                    // }

                    // if (isEqual) {
                    //     label = field.content !== null ? this.$t(field.content) + ': ' : null;
                    //     content = label !== null ? label + value : value;
                    // } else {
                    //     label = field.content !== null ? this.$t(field.content) + ': ' : null;
                    //     let oldValue = !_.isEmpty(existingData[key]) ? `<span class="fw-semibold">${existingData[key]}</span>` : null;
                    //     let oldData = `${label !== null ? label + oldValue : oldValue} `;
                    //     let newData = `${label !== null ? label + value : value}`;

                    //     content = `<span class="bkg-danger-light p-0 m-0 d-table"><i class="fa fa-minus fa-sm"></i> ${oldData}</span><span class="bkg-success-light p-0 m-0 d-table"><i class="fa fa-plus fa-sm"></i> ${newData}</span>`;
                    // }

                    // if (content !== 'null') {
                    list.push(content);
                    // }
                }
            }
            return list;
        },
        formatChangeLogData(name, newValue, oldValue, isEqual) {
            let value;
            let label = name !== null ? this.$t(name) + ': ' : null;
            let content;
            if (Array.isArray(newValue)) {
                value = newValue.length > 0 ? `<span class="fw-semibold"> ${newValue}</span>` : null;
            } else {
                value = !_.isEmpty(newValue) ? `<span class="fw-semibold">${newValue}</span>` : null;
            }

            if (isEqual) {
                content = label !== null ? label + value : value;
            } else {
                let existingValue = !_.isEmpty(oldValue) ? `<span class="fw-semibold">${oldValue}</span>` : null;
                let existingData = `${label !== null ? label + existingValue : existingValue} `;
                let newData = `${label !== null ? label + value : value}`;

                content = `<span class="bkg-danger-light p-0 m-0 d-table"><i class="fa fa-minus fa-sm"></i> ${existingData}</span><span class="bkg-success-light p-0 m-0 d-table"><i class="fa fa-plus fa-sm"></i> ${newData}</span>`;
            }

            if (content !== 'null') {
                return content;
                //list.push(content);
            }

        }
    },
    mounted() {
    }
}
</script>

<style>
span.bkg-danger-light {
    background-color: #FFEBE9;
}

span.bkg-success-light {
    background-color: #E6FFEB;
}
</style>
