<template>
    <div>
        <span class="mb-0 font-weight-bold">{{ processName }}
            <a
                @click="showProcessElements = !showProcessElements"
                :aria-expanded="showProcessElements ? 'true' : 'false'"
            >
                <i
                class="fa"
                :class="{
                    'fa-chevron-right': !showProcessElements,
                    'fa-chevron-down': showProcessElements,
                }"
                style="width: 14px"
                ></i>
            </a>
        </span>
            <div>
            <!-- <pre
                class="mt-1 mb-0"
                @click="
                showProcessElements = !showProcessElements
                ">{{ initDockerfile.split("\n")[0] }} <template v-if="!showDockerfile">...</template></pre> -->
            <b-collapse id="processelements" v-model="showProcessElements" :aria-hidden="showProcessElements ? 'false' : 'true'">
                <!-- <pre>{{ initDockerfile.split("\n").slice(1).join("\n") }}</pre> -->
            </b-collapse>
            </div>
    </div>
</template>

<script>
export default {
    props: ["processName"],
    components: {},
    mixins: [],
    data() {
        return {
            showProcessElements: false,
        }
    },
    methods: {      
    },
    mounted() {
    }
}
</script>

<style>

</style>
