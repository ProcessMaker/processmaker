
<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';

const router = useRouter();
const route = useRoute();
</script>

<template>
  <div>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-item nav-link"
              :class="{ active: route.name === 'index' }"
              role="tab"
              @click="router.push({ name: 'index' })">
                {{ $t('Linked Instances') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-item nav-link"
              :class="{ active: route.name === 'local-bundles' }"
              role="tab"
              @click="router.push({ name: 'local-bundles' })">
                {{ $t('Local Bundles') }}
            </a>
        </li>
    </ul>

    <div>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="nav-linked-instances" role="tabpanel">
                <div class="card card-body p-3 border-top-0">
                    <router-view></router-view>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>