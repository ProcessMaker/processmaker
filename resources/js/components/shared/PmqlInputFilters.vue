<template>
    <div class="filter-dropdown-panel-container card">
      <template v-slot:filters>
        <div v-if="searchType == 'requests'" class="card-body">
            <label for="process_name_filter" class="d-none">{{$t('Process')}}</label>
            <multiselect id="process_name_filter" v-model="process"
              class="mb-3"
              @search-change="getProcesses"
              @input="buildPmql"
              :show-labels="true"
              :loading="isLoading.process"
              open-direction="bottom"
              label="name"
              :options="processOptions"
              :track-by="'id'"
              :multiple="true"
              :aria-label="$t('Process')"
              :placeholder="$t('Process')">
              <template slot="noResult">
                  {{ $t('No Results') }}
              </template>
              <template slot="noOptions">
                  {{ $t('No Data Available') }}
              </template>
              <template slot="selection" slot-scope="{ values, search, isOpen }">
                  <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('processes') }}</span>
              </template>
            </multiselect>
          <label for="process_status_filter" class="d-none">{{$t('status')}}</label>
          <multiselect id="process_status_filter" v-model="status"
            class="mb-3"
            :show-labels="true"
            @input="buildPmql"
            :loading="isLoading.status"
            open-direction="bottom"
            label="name"
            :options="statusOptions"
            track-by="value"
            :multiple="true"
            :aria-label="$t('Status')"
            :placeholder="$t('Status')">
              <template slot="noResult">
                  {{ $t('No Results') }}
              </template>
              <template slot="noOptions">
                  {{ $t('No Data Available') }}
              </template>
              <template slot="selection" slot-scope="{ values, search, isOpen }">
                  <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('statuses') }}</span>
              </template>
            </multiselect>
          <label for="process_requester_filter" class="d-none">{{$t('Requester')}}</label>
          <multiselect id="process_requester_filter"
            v-model="requester"
            @search-change="getRequesters"
            @input="buildPmql"
            class="mb-3"
            :show-labels="true"
            :loading="isLoading.requester"
            open-direction="bottom"
            label="fullname"
            :options="requesterOptions"
            :track-by="'id'"
            :multiple="true"
            :aria-label="$t('Requester')"
            :placeholder="$t('Requester')">
              <template slot="noResult">
                  {{ $t('No Results') }}
              </template>
              <template slot="noOptions">
                  {{ $t('No Data Available') }}
              </template>
              <template slot="selection" slot-scope="{ values, search, isOpen }">
                  <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('requesters') }}</span>
              </template>
              <template slot="option" slot-scope="props">
                  <img v-if="props.option.avatar.length > 0" class="option__image"
                      :src="props.option.avatar">
                  <span v-else class="initials bg-warning text-white p-1"> {{getInitials(props.option.firstname, props.option.lastname)}}</span>
                  <span class="ml-1">{{props.option.fullname}}</span>
              </template>
            </multiselect>
          <label for="process_participant_filter" class="d-none">{{$t('Participants')}}</label>
          <multiselect id="process_participant_filter" v-model="participants"
            @search-change="getParticipants"
            @input="buildPmql"
            class="mb-3"
            :show-labels="true"
            :loading="isLoading.participants"
            open-direction="bottom"
            label="fullname"
            :options="participantsOptions"
            :track-by="'id'"
            :multiple="true"
            :aria-label="$t('Participants')"
            :placeholder="$t('Participants')">
              <template slot="noResult">
                  {{ $t('No Results') }}
              </template>
              <template slot="noOptions">
                  {{ $t('No Data Available') }}
              </template>
              <template slot="selection" slot-scope="{ values, search, isOpen }">
                  <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('requesters') }}</span>
              </template>
              <template slot="option" slot-scope="props">
                  <img v-if="props.option.avatar.length > 0" class="option__image"
                      :src="props.option.avatar">
                  <span v-else class="initials bg-warning text-white p-1"> {{getInitials(props.option.firstname, props.option.lastname)}}</span>
                  <span class="ml-1">{{props.option.fullname}}</span>
              </template>
            </multiselect>
        </div>
        <div v-if="searchType == 'tasks'" class="search-bar-inputs flex-grow d-flex flex-column flex-md-row w-100">
          <label for="process_request_filter" class="d-none">{{$t('Request')}}</label>
          <multiselect id="process_request_filter"
            v-model="request"
            @search-change="getRequests"
            @input="buildPmql"
            class="mb-3"
            :show-labels="true"
            :loading="isLoading.request"
            open-direction="bottom"
            label="name"
            :options="requestOptions"
            :track-by="'id'"
            :multiple="true"
            :aria-label="$t('Request')"
            :placeholder="$t('Request')">
              <template slot="noResult">
                  {{ $t('No Results') }}
              </template>
              <template slot="noOptions">
                  {{ $t('No Data Available') }}
              </template>
              <template slot="selection" slot-scope="{ values, search, isOpen }">
                  <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('requests') }}</span>
              </template>
            </multiselect>
          <label for="process_task_filter" class="d-none">{{$t('Task')}}</label>
          <multiselect id="process_task_filter"
            v-model="name"
            @search-change="getNames"
            @input="buildPmql"
            class="mb-3"
            :show-labels="true"
            :loading="isLoading.name"
            open-direction="bottom"
            label="name"
            :options="nameOptions"
            :track-by="'name'"
            :multiple="true"
            :aria-label="$t('Task')"
            :placeholder="$t('Task')">
              <template slot="noResult">
                  {{ $t('No Results') }}
              </template>
              <template slot="noOptions">
                  {{ $t('No Data Available') }}
              </template>
              <template slot="selection" slot-scope="{ values, search, isOpen }">
                  <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('names') }}</span>
              </template>
            </multiselect>
          <label for="process_status_options_filter" class="d-none">{{$t('Status')}}</label>
          <multiselect id="process_status_options_filter"
            v-model="status"
            class="mb-3"
            :show-labels="true"
            @input="buildPmql"
            :loading="isLoading.status"
            open-direction="bottom"
            label="name"
            :options="statusOptions"
            track-by="value"
            :multiple="true"
            :aria-label="$t('Status')"
            :placeholder="$t('Status')">
              <template slot="noResult">
                  {{ $t('No Results') }}
              </template>
              <template slot="noOptions">
                  {{ $t('No Data Available') }}
              </template>
              <template slot="selection" slot-scope="{ values, search, isOpen }">
                  <span class="multiselect__single" v-if="values.length > 1 && !isOpen">{{ values.length }} {{ $t('statuses') }}</span>
              </template>
            </multiselect>
            <div class="card-footer">
              <button class="btn btn-secondary-outline">Reset</button>
              <button class="btn btn-primary">Apply</button>
            </div>
        </div>
        <div class="search-bar-actions d-flex flex-shrink mt-3 mt-md-0">
            <div class="search-bar-additions">
              <div v-for="addition in additions">
                <component :is="addition" :permission="permission"></component>
              </div>
            </div>
        </div>
      </template>
    </div>
</template>

<script>

export default {
  props: ["searchType"],
  data() {
    return {
      pmql: "",
    };
  },
  watch: {
  },
  mounted() {
  },
  methods: {
  },
};
</script>

<style lang="scss">
.filter-dropdown-panel-container {
  min-width: 20rem;
  background: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.125);
  box-shadow: 0 6px 12px 2px rgba(0, 0, 0, 0.168627451);
  position: absolute;
  left: 0;
  top: 2.5rem;
  border-radius: 3px;
  z-index: 1;
  max-width: 30rem;
}
</style>
