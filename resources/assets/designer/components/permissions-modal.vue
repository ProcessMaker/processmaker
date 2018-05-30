<template>
  <div class="modal fade" id="permissonsmodal" tabindex="-1" role="dialog" aria-labelledby="permissonsmodal" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title" id="permissonsmodal">Create Permissions</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body">
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
            <div>
              <label>Participation Required?</label>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio1" value="option1">
                <label class="form-check-label" for="inlineRadio1">Yes</label>
              </div>

              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="option2">
                <label class="form-check-label" for="inlineRadio2">No</label>
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
      </div>
    </div>
  </div>
</template>
