<template>
  <b-modal ref="modal" size="lg" centered title="Create Permissions">
      <form>
        <div class="form-group">
          <label for="casesStatus">Cases Status</label>
          <select class="form-control" id="casesStatus">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
          </select>
        </div>

        <div class="form-group">
          <label for="targetTask">Target Task</label>
          <select class="form-control" id="targetTask">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
          </select>
        </div>

        <div class="form-group">
          <label for="groupOrUser">Group or User</label>
          <input type="text" class="form-control" id="groupOrUser">
        </div>

        <div class="form-group">
        <label for="originTask">Origin Task</label>
        <select class="form-control" id="originTask">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
        </select>
        </div>
        <div class="form-group">
          <label>Participation Required?</label>
          <div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio1" value="option1">
            <label class="form-check-label" for="inlineRadio1">Yes</label>
          </div>

          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="option2">
            <label class="form-check-label" for="inlineRadio2">No</label>
          </div>
          </div>
        </div>
        <div class="form-group">
        <label for="typeSelect">Type</label>
        <select class="form-control" id="typeSelect">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
        </select>
        </div>

        <div class="form-group">
        <label for="PermissionsSelect">Permission</label>
        <select class="form-control" id="PermissionsSelect">
          <option>1</option>
          <option>2</option>
          <option>3</option>
          <option>4</option>
          <option>5</option>
        </select>
        </div>
    </form>

  </b-modal>
</template>

<script>
export default {
  data() {
    return {
      // form models here
    }
  }
  mounted() {
    // Show our modal as soon as we're created
    this.$refs.modal.show();
  }
};
</script>

