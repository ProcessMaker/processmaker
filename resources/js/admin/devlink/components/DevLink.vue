
<script setup>
</script>

<template>
  <div>
    <ul class="nav nav-tabs"> <li class="nav-item">
            <a class="nav-item nav-link active" id="nav-users-tab" data-toggle="tab" href="#nav-linked-instances" role="tab"
            onclick="return;" aria-controls="nav-linked-instances" aria-selected="true">
                {{ $t('Linked Instances') }}
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