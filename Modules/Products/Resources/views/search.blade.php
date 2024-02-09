@foreach($products as $product)
<div class="search-product-item p-2" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-price="{{ $product->price }}" data-weight="{{ $product->weight }}">
    <span class="search-product-item__name">{{ $product->name }}</span>
</div>
@endforeach