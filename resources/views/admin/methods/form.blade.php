@extends('layouts.admin')
@section('title', $method->exists ? 'Edit way clients pay you' : 'Add a way clients pay you')
@section('heading', $method->exists ? 'Edit way clients pay you' : 'Add a way clients pay you')

@section('content')
<a href="{{ route('admin.methods.index') }}" class="muted" style="font-size:14px">← Back</a>
@include('admin.methods._form', ['method' => $method])
@endsection
