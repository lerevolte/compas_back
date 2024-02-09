@foreach($products as $remnant)
<div class="search-product-item p-2" data-id="{{ $remnant->id }}" data-product="{{ $remnant->product->name }}" data-name="{{ $remnant->name }}" data-price="{{ $remnant->price }}" data-weight="{{ $remnant->weight }}">
    <span class="search-product-item__name">{{ $remnant->name }}</span>
</div>
@endforeach