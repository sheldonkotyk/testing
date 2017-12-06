@extends('default')

@section('content')

@if (Session::has('error'))
  {!! trans(Session::get('error')) !!}
@elseif (Session::has('status'))
  An email with the password reset has been sent.
@endif
 
{!! Form::open(array('route' => 'password.request')) !!}
 
  <p>{!! Form::label('email', 'Email') !!}
  {!! Form::text('email') !!}</p>
 
  <p>{!! Form::submit('Submit') !!}</p>
 
{!! Form::close() !!}

@stop