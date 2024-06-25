<template>
    <div class="d-flex align-items-center">
        <custom-icon :name="completedIcon" color="#4EA075" />
        <span class="text-summary">{{ completed }}% {{ $t('Completed') }}</span>
        <custom-icon :name="inProgressIcon" color="#478FCC" />
        <span class="text-summary">{{ inProgress }}% {{ $t('In Progress') }}</span>
    </div>
</template>

<script>
import CustomIcon from "../components/utils/CustomIcon.vue";

export default {
    components: {
        CustomIcon
    },
    props: {
        completedIcon: {
            type: String,
            required: true
        },
        inProgressIcon: {
            type: String,
            required: true
        },
        completed: {
            type: Number,
            required: true
        },
        inProgress: {
            type: Number,
            required: true
        }
    }
}
</script>

<style scoped>
.text-summary {
    color: #B1B8BF;
    font-size: 16px;
    font-weight: 400;
    vertical-align: middle;
    font-style: italic;
}
.d-flex.align-items-center > * {
  margin-right: 8px;
}
</style>