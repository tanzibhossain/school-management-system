@extends('layouts.admin')
@section('title', __('Account & Security'))
@section('content')
  @include('admin.partials.page-header', ['title' => __('Account & Security'), 'crumbs' => [__('Account & Security')]])
  @include('partials.account-settings')
@endsection
