  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h4 class="modal-title">Warning!</h4>
  </div>
  <div class="modal-body">
  	<p class="lead"><b>This cannot be undone.</b></p>
    <p class="lead">Deleting this program is permanent. Anything belonging to this program will no longer be accessible in your database. All programs listed as children of this program will move up one level.</p>
    <p class="lead">Please be sure that this is what you want to do. HelpYouSponsor will not be responsible for any data that is lost.</p>

    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    <a href="{!! URL::to('admin/remove_program', array($program_id)) !!}" type="button" class="btn btn-danger">Delete Program</a>
  </div>