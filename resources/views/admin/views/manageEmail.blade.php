@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
    
    <h2>{!!Client::find(Session::get('client_id'))->organization!!} Auto Emails <small> <span class="glyphicon glyphicon-send"></span> View All Auto Emails</small></h2>
	
	@include('admin.views.manageEmailTemplatesMenu')

	<div class="app-body">
	<div class="magic-layout">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-send"></i></div>
                <div class="panel-actions">
                    <div class="badge">{!!count($emailsets)!!} Sets</div>
                </div>
               
                <h3 class="panel-title">All Auto Emails</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

				<table class="table table-striped">
					<tr>
						<th>Auto Email Set Name</th>
						<th>Manage Auto Emails</th>
						<th>Remove</th>
					</tr>
					@foreach ($emailsets as $emailset)
					<tr>
						<td><a href="{!! URL::to('admin/edit_emailset', array($emailset->id)) !!}"><span class="glyphicon glyphicon-pencil"></span> {!! $emailset->name !!}</a></td>
						<td>
							<ul class="list-unstyled">

								@foreach($t_array as $k=>$t)
									<li><a href="{!! URL::to('admin/edit_emailtemplate', array($emailset->id, $k)) !!}"><span class="glyphicon glyphicon-envelope"></span> {!!$t['title']!!}</a>						
										@if(isset($template_errors[$emailset->id][$k]))
											{!!reset($template_errors[$emailset->id][$k])!!}
										@endif
									</li>
								@endforeach
							</ul>
						</td>
						<td><a href="{!! URL::to('admin/remove_emailset', array($emailset->id)) !!}"><span class="glyphicon glyphicon-remove"></span></a></td>
					</tr>
					@endforeach
				</table>
				</div>
				</div>
				</div>
				</div>
@stop

@section('footerscripts')
@stop