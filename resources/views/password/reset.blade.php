@extends('default')

@section('content')

@if (Session::has('error'))
  {!! trans(Session::get('error')) !!}
@endif
 
{!! Form::open(array('route' => array('password.update', $token))) !!}
 
  <p>{!! Form::label('email', 'Email') !!}
  {!! Form::text('email') !!}</p>
 
  <p>{!! Form::label('password', 'Password') !!}
  {!! Form::password('password') !!}</p>
 
  <p>{!! Form::label('password_confirmation', 'Password confirm') !!}
  {!! Form::password('password_confirmation') !!}</p>
 
  {!! Form::hidden('token', $token) !!}
 
  <p>{!! Form::submit('Submit') !!}</p>
 
{!! Form::close() !!}

@stop