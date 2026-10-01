@extends('frontend.layouts.app')
@section('title', $category->name)
@section('content')
<h2 class="mb-2">{{ $category->name }}</h2>
<p class="text-muted mb-4">{{ $category->description }}</p>
<div class="row">
    @forelse($products as $product)
    <div class="col-md-3 col-6 mb-4">
        <div class="card product-card h-100">
            <img src="{{ $product->image_url }}" class="card-img-top" style="height:180px;object-fit:cover;" alt="{{ $product->name }}" onerror="this.onerror=null;this.src='{{ asset('images/default-product.svg') }}';">
            <div class="card-body">
                <h6>{{ $product->name }}</h6>
                <span class="price">₹{{ number_format($product->display_price,0) }}</span>
                <a href="{{ route('products.show', $product->slug) }}" class="btn btn-sm btn-primary float-end">View</a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center text-muted py-5">No products in this category yet.</div>
    @endforelse
</div>
{{ $products->links() }}
@endsection
