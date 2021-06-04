@extends('layouts.app')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Create Product</h1>
    </div>
    <div id="app">
        @if(count($variants)==0)
            <h2>No Variants are available</h2>
            <h3 class="text-danger">You have to <a href="{{ route('product-variant.create') }}">create variant</a> first.</h3>
        @else
            <create-product :variants="{{ $variants }}">Loading</create-product>
        @endif
    </div>
@endsection
