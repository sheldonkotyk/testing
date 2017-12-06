@extends('admin.default')

@section('content')
    @if (Session::get('error'))
        <div class="alert alert-{!! Session::get('info-alert') !!}">{!! Session::get('info') !!}</div>
    @else
    	There is no info.
    @endif
@stop