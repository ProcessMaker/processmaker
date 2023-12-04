<template>
    <div  class="mt-3">
        <b-tabs content-class="mt-3 text-style">
            <b-tab title="My Requests" active>
                <div class="d-flex align-items-center justify-content-center flex-column">
                    <img src="/img/launchpad-images/frame.png" :alt="$t('No Image')">
                    <div class="text-center">
                        <strong>{{ $t('You have made no requests of this process') }}</strong>
                    </div>
                    <div class="text-center">
                        {{ $t('All your requests will be shown here') }}
                    </div>
                </div>
            </b-tab>
            <b-tab title="My Tasks">
                <div class="d-flex align-items-center justify-content-center flex-column">
                    <img src="/img/launchpad-images/frame.png" :alt="$t('No Image')">
                    <div class="text-center mt-3">
                        <strong>{{ $t('You have no tasks from this process') }}</strong>
                    </div>
                    <div class="text-center mt-1">
                        {{ $t('All your tasks related to this process will be shown here') }}
                    </div>
                </div>
            </b-tab>
        </b-tabs>
    </div>
</template>

<script>
export default {
    data() {
    return {

    }
}
}
</script>
<style>
.text-style {
  margin-bottom: 10px;
  color: #556271;
}    
</style>
