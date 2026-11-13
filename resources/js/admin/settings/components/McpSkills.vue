<template>
  <div class="setting-text">
    <div v-if="!skills.length" class="font-italic text-black-50">
      Empty
    </div>
    <div v-else>
      {{ summary }}
    </div>
    <b-modal
      class="setting-object-modal"
      v-model="showModal"
      size="lg"
      @hidden="onModalHidden"
    >
      <template v-slot:modal-header class="d-block">
        <div>
          <h5 class="mb-0" v-if="setting.name">{{ $t(setting.name) }}</h5>
          <h5 class="mb-0" v-else>{{ setting.key }}</h5>
          <small class="form-text text-muted" v-if="setting.helper">{{ $t(setting.helper) }}</small>
        </div>
        <button type="button" :aria-label="$t('Close')" class="close" @click="onCancel">×</button>
      </template>

      <div v-if="mode === 'list'">
        <div class="d-flex justify-content-end mb-2">
          <b-button variant="secondary" size="sm" @click="onAdd">
            <i class="fa fa-plus" /> {{ $t('Add') }}
          </b-button>
        </div>

        <div v-if="!draft.length" class="font-italic text-black-50 text-center py-4">
          {{ $t('No skills configured') }}
        </div>

        <div
          v-for="(skill, index) in draft"
          :key="skill.id"
          class="d-flex align-items-center border rounded px-3 py-2 mb-2"
        >
          <div class="flex-grow-1 text-truncate">
            <div class="d-flex align-items-center">
              <span class="text-truncate">{{ skill.name || $t('Untitled skill') }}</span>
              <span
                v-if="skill.system"
                class="badge badge-secondary ml-2"
              >{{ $t('System') }}</span>
            </div>
            <small v-if="skill.when" class="text-muted text-truncate d-block">{{ skill.when }}</small>
          </div>
          <b-button
            variant="link"
            class="px-2"
            :aria-label="$t('Edit')"
            :disabled="skill.system"
            @click="onEditSkill(index)"
          >
            <i class="fa fa-edit" />
          </b-button>
          <b-button
            variant="link"
            class="px-2"
            :aria-label="$t('Delete')"
            :disabled="skill.system"
            @click="onRemove(index)"
          >
            <i class="fa fa-trash" />
          </b-button>
        </div>
      </div>

      <div v-else>
        <b-form-group :label="$t('Name')">
          <b-form-input
            v-model="form.name"
            :placeholder="$t('Name')"
            spellcheck="false"
            autocomplete="off"
          />
        </b-form-group>
        <b-form-group
          :label="$t('When to use')"
          :description="$t('Short hint shown in the MCP catalog so clients load this skill only when relevant')"
        >
          <b-form-input
            v-model="form.when"
            :placeholder="$t('e.g. Before create-screen when language or labels matter')"
            spellcheck="false"
            autocomplete="off"
          />
        </b-form-group>
        <b-form-group :label="$t('Markdown')">
          <b-form-textarea
            v-model="form.content"
            rows="12"
            :placeholder="$t('Markdown skill content')"
            spellcheck="false"
          />
        </b-form-group>
      </div>

      <div slot="modal-footer" class="w-100 m-0 d-flex">
        <template v-if="mode === 'list'">
          <button type="button" class="btn btn-outline-secondary ml-auto" @click="onCancel">
            {{ $t('Cancel') }}
          </button>
          <button
            type="button"
            class="btn btn-secondary ml-3"
            @click="onSave"
            :disabled="!changed"
          >
            {{ $t('Save') }}
          </button>
        </template>
        <template v-else>
          <button type="button" class="btn btn-outline-secondary ml-auto" @click="onCancelForm">
            {{ $t('Cancel') }}
          </button>
          <button
            type="button"
            class="btn btn-secondary ml-3"
            @click="onSaveForm"
            :disabled="!formValid"
          >
            {{ $t('Save') }}
          </button>
        </template>
      </div>
    </b-modal>
  </div>
</template>

<script>
import settingMixin from "../mixins/setting";

const slugify = (value) => String(value || "")
  .toLowerCase()
  .trim()
  .replace(/[^a-z0-9]+/g, "-")
  .replace(/^-+|-+$/g, "") || `skill-${Date.now()}`;

const newSkill = (partial = {}) => {
  const name = partial.name || "";
  const id = partial.id || `skill-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  return {
    id,
    name,
    slug: partial.slug || slugify(name || id),
    when: partial.when || "",
    content: partial.content || "",
    system: Boolean(partial.system),
  };
};

const normalizeSkills = (value) => {
  if (!Array.isArray(value)) {
    return [];
  }
  return value.map((item) => newSkill(item || {}));
};

export default {
  mixins: [settingMixin],
  props: ["value", "setting"],
  data() {
    return {
      skills: [],
      draft: [],
      showModal: false,
      mode: "list",
      editingIndex: null,
      form: newSkill(),
    };
  },
  computed: {
    changed() {
      return JSON.stringify(this.skills) !== JSON.stringify(this.normalizedDraft());
    },
    formValid() {
      return Boolean((this.form.name || "").trim() || (this.form.content || "").trim());
    },
    summary() {
      const names = this.skills.map((skill) => skill.name).filter(Boolean);
      if (!names.length) {
        return `${this.skills.length} skill(s)`;
      }
      return this.trimmed(names.join(", "));
    },
  },
  watch: {
    value: {
      immediate: true,
      handler(value) {
        this.skills = normalizeSkills(value);
      },
    },
  },
  methods: {
    normalizedDraft() {
      return this.draft.map((skill) => ({
        id: skill.id,
        name: (skill.name || "").trim(),
        slug: slugify(skill.slug || skill.name || skill.id),
        when: (skill.when || "").trim(),
        content: skill.content || "",
        ...(skill.system ? { system: true } : {}),
      })).filter((skill) => skill.name || skill.content);
    },
    resetForm() {
      this.mode = "list";
      this.editingIndex = null;
      this.form = newSkill();
    },
    onAdd() {
      this.editingIndex = null;
      this.form = newSkill();
      this.mode = "form";
    },
    onEditSkill(index) {
      if (this.draft[index]?.system) {
        return;
      }
      this.editingIndex = index;
      this.form = this.copy(this.draft[index]);
      this.mode = "form";
    },
    onRemove(index) {
      if (this.draft[index]?.system) {
        return;
      }
      this.draft.splice(index, 1);
    },
    onCancelForm() {
      this.resetForm();
    },
    onSaveForm() {
      if (!this.formValid) {
        return;
      }

      const skill = {
        id: this.form.id,
        name: (this.form.name || "").trim(),
        slug: slugify(this.form.slug || this.form.name || this.form.id),
        when: (this.form.when || "").trim(),
        content: this.form.content || "",
        ...(this.form.system ? { system: true } : {}),
      };

      if (this.editingIndex === null) {
        this.draft.push(skill);
      } else {
        this.$set(this.draft, this.editingIndex, skill);
      }

      this.resetForm();
    },
    onCancel() {
      this.showModal = false;
    },
    onEdit() {
      this.draft = this.copy(this.skills);
      this.resetForm();
      this.showModal = true;
    },
    onModalHidden() {
      this.draft = this.copy(this.skills);
      this.resetForm();
    },
    onSave() {
      this.skills = this.normalizedDraft();
      this.showModal = false;
      this.emitSaved(this.skills);
    },
  },
};
</script>
