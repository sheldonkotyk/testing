@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
<h2>{!!Client::find(Session::get('client_id'))->organization!!} Settings <small><span class="glyphicon glyphicon-cog"></span> View All Settings</small></h2>
	@include('admin.views.settingsMenu')
	<div class="app-body">
	 <div class="magic-layout">
                          
            <div id="panel-bsbutton" class="panel panel-default magic-element width-full">
                <div class="panel-heading">
                    <div class="panel-icon"><i class="icon ion-ios7-gear"></i></div>
                    <div class="panel-actions">
                            <span class="badge">{!!count($settings)!!} Settings</span>
                    </div>
                    <h3 class="panel-title">All Settings</h3>
                </div><!-- /panel-heading -->
                <div class="panel-body">
				<table class="table table-striped">
					<tr>
						<th>Name</th>
						<th>Used By</th>
						<th>Remove</th>
					</tr>
					@if(!empty($settings))
						@foreach ($settings as $setting) 
							<tr>
								<td><a href="{!! URL::to('admin/edit_settings') !!}/{!! $setting->id !!}"><span class="glyphicon glyphicon-pencil"></span> {!! $setting->name !!}</a></td>
								@if(!empty($setting->programs))
									<td>
									@foreach($setting->programs as $program)
										{!! $program->name !!}<br>
									@endforeach
									</td>
								@else
									<td>Not Used</td>
								@endif
								<td><a href="{!! URL::to('admin/remove_settings') !!}/{!! $setting->id !!}"><span class="glyphicon glyphicon-remove"></span></a></td>
							</tr>
						@endforeach
					@else
						<tr>
							<td>No Settings</td>
							<td></td>
							<td></td>
						</tr>
					@endif
					</table>
					</div>
					</div>
					</div>
					</div>

	
@stop

@section('footerscripts')
@stop