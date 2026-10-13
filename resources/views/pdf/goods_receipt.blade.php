<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8"/>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: "DejaVu Sans", sans-serif; color: #000; }
        .doc { page-break-after: always; }
        .doc:last-child { page-break-after: auto; }
@include('pdf.partials.goods_receipt_style')
    </style>
</head>
<body>
@foreach($documents as $doc)
    <div class="doc">
        @include('pdf.partials.goods_receipt_doc', ['doc' => $doc])
    </div>
@endforeach
</body>
</html>
