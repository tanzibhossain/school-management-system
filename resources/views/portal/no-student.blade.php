@extends('layouts.portal')
@section('title', __('Portal'))
@section('heading', 'Family Portal')
@section('content')
  <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i> {{ __('No student record is linked to your account yet. Please contact the school office.') }}</div>
@endsection
