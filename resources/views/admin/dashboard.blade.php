@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
  <h1 class="h4 mb-4">Dashboard</h1>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Active students</div>
        <div class="h3 mb-0">{{ $studentCount }}</div>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Active staff</div>
        <div class="h3 mb-0">{{ $staffCount }}</div>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small">Classes</div>
        <div class="h3 mb-0">{{ $classCount }}</div>
      </div></div>
    </div>
  </div>
@endsection
