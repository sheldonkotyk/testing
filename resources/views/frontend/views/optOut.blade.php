@extends('default')

@section('content')
    @if (Session::get('message'))
    	<div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

<h1>Email Opt-Out</h1>
<p>Thank you! You will no longer receive emails from our system.</p> 
@stop