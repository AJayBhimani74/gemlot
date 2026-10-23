@extends('layouts.app')

@section('title', 'Edit '.$stone->sku)

@section('content')
    <div class="card narrow">
        <h1>Edit {{ $stone->sku }}</h1>
        <form method="POST" action="{{ route('seller.stones.update', $stone) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('seller.stones._form')
            <button class="btn" type="submit">Update</button>
        </form>
    </div>
@endsection
