@extends('admin.default')

@section('content')
    @if (Session::get('error'))
        <div class="alert alert-{!! Session::get('error-alert') !!}">{!! Session::get('error') !!}</div>
    @else
    	There are no errors.
    @endif
@stop