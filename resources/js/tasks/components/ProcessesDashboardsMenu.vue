<template>
    <div class="inbox-process-menu">
        <div class="menu-options">
            <button 
                class="menu-btn" 
                @click="openParticipantProcess('complete')"
            >
                <i class="fas fa-check"></i> Proceso 1 
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ProcessesDashboardsMenu',
    data() {
        return {
            selectedProcess: null,
        }
    },
    props: {
        selectedTask: {
            type: Object,
            default: null
        }
    },

    methods: {
        openParticipantProcess(item) {
            this.$emit('processParticipantSelected', item);
        },
        processTask(action) {
            this.$emit('process-task', {
                taskId: this.selectedTask.id,
                action: action
            });
        }
    }
}
</script>

<style scoped>
.inbox-process-menu {
    padding: 1rem;
    border: 1px solid #eee;
    border-radius: 4px;
}

.menu-options {
    display: flex;
    gap: 1rem;
}

.menu-btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    background-color: #f0f0f0;
    cursor: pointer;
}

.menu-btn:hover {
    background-color: #e0e0e0;
}

.menu-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.selected-task {
    margin-top: 1rem;
    padding: 0.5rem;
    background-color: #f8f8f8;
    border-radius: 4px;
}
</style>
