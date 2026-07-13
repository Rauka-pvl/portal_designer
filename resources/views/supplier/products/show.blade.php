@extends('layouts.supplier')

@section('title', $product->name)
@section('header_title', $product->name)

@section('content')
    @include('partials.product-detail', [
        'product' => $product,
        'backUrl' => route('supplier.products.index'),
        'backLabel' => __('products.back'),
    ])
@endsection
