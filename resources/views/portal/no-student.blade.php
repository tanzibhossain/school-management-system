@extends('layouts.portal')
@section('title', __('Portal'))
@section('heading', 'Family Portal')
@section('content')
  <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i> {{ __('No Student Record Is Linked To Your Account Yet. Please Contact The School Office.') }}</div>
@endsection
