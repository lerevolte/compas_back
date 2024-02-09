<body>
    <?
    function isMobile() {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
    }
    $date_string = '';
    if(isset($date)) {
        $date_string = $date;
        if(isset($date_end) && $date != $date_end)
            $date_string = $date.'-'.$date_end;
    }
    
    ?>
<div class="project">

    @include('blocks.navbar_new')
    <header class="page-header">
        @if(isMobile() && isset($order))
        <div class="container mb-2 d-lg-none">
            <div class="search mw-100">
            <form class="js-search-order form">
                <div class="position-relative">
                    <input name="order" type="text" class="form-control" placeholder="Поиск по заказам" autocomplete="off" value="<?=($order ? $order : '')?>">
                    <div class="search-results"></div>
                </div>
            </form>
            </div>
        </div>
        @endif
        
        
        <div class="container h-100 d-flex align-items-center">
            <h1 class="my-0 h1">{{ $title }}</h1>
            @if(isset($date))
            <form class="filter-date-store js-filter-date d-flex align-items-center justify-content-between justify-content-sm-center me-3">
                <label class="btn btn-date mb-0">
                    <!-- <svg class="icon icon-date"><use xlink:href="#icon-date"></use></svg> -->
                    <input type="hidden" name="name" value="<?=isset($_GET['name']) ? $_GET['name']:''?>">
                    <input name="date" type='text' class="datepicker-here form-control form-control-date" data-position="right top" value="{{ $date_string }}" autocomplete="off" placeholder="" id="js-date-control" style=" padding: 0;width: {{ strstr($date_string,'-') ? '180px': '105px'}}"/>
                </label>
            </form>
            @endif
            @section('stores_nav')
            @show
            
            @if(!isMobile() && isset($order))
            <div class="ms-auto search d-none d-lg-block">
                <form class="js-search-order form">
                    <div class="position-relative">
                        <input name="order" type="text" class="form-control" placeholder="Поиск по заказам" autocomplete="off" value="<?=( $order ? $order : '')?>">
                        <div class="search-results"></div>
                    </div>
                </form>
            </div>
            @endif
            <a class="btn btn-light d-lg-none px-2 ms-auto" data-bs-toggle="offcanvas" href="#offcanvasSidebar" role="button" aria-controls="offcanvasSidebar">
                <svg class="icon icon-menu"><use xlink:href="#icon-menu"></use></svg>
            </a>
        </div>
    </header> <!-- Page Header : end -->
    <main class="page-content">            
        <div class="container">