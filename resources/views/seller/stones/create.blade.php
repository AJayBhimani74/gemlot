@extends('layouts.app')

@section('title', 'Add stone')

@section('content')
    <div class="card narrow">
        <h1>Add a stone</h1>
        <form method="POST" action="{{ route('seller.stones.store') }}" enctype="multipart/form-data">
            @csrf
            @include('seller.stones._form')
            <button class="btn" type="submit">Save</button>
        </form>
    </div>
@endsection
