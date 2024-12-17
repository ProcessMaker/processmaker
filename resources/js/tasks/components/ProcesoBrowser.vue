<template>
    <div>
        <h2>Proceso Browser</h2>
        <p v-if="procesoId">ID del proceso: {{ procesoId }}</p>
        <p v-else>No hay proceso seleccionado</p>
    </div>
</template>

<script>
export default {
    name: 'ProcesoBrowser',
    props: {
        procesoId: {
            type: [String, null],
            default: null
        }
    }
}
</script>