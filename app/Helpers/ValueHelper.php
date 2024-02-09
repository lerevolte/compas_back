<?
namespace App\Helpers;

class ValueHelper
{
    public static function shout(string $string)
    {
        return strtoupper($string);
    }

    public static function checkVal($param) {
	    if ($param && $param != 'null' && $param != null) {
	        return 1;
	    }
	}
	public static function incrementalHash($len = 7){
	  $charset = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	  $base = strlen($charset);
	  $result = '';

	  $now = explode(' ', microtime())[1];
	  while ($now >= $base){
	    $i = $now % $base;
	    $result = $charset[$i] . $result;
	    $now /= $base;
	  }
	  return substr($result, -7);
	}
	public static function convertToHoursSecs($time, $format = '%02d:%02d') {
	    if ($time < 1) {
	        return;
	    }
	    $hours = floor($time / 3600);
	    $minutes = floor($time % 3600)/60;
	    return sprintf($format, $hours, $minutes);
	}
	public static function convertToHoursMins($time, $format = '%02d:%02d') {
	    if ($time < 1) {
	        return '00:00';
	    }
	    $hours = floor($time / 60);
	    $minutes = ($time % 60);
	    return sprintf($format, $hours, $minutes);
	}
	public static function convertToHoursMinsString($time, $format = '%02d:%02d') {
	    if ($time < 1) {
	        return '0 ч 0 м';
	    }
	    $hours = floor($time / 60);
	    $minutes = ($time % 60);
	    if($hours>0)
	        return $hours.' ч '.$minutes.' м';
	    else
	        return $minutes.' м';
	}

	public static function sortNestedArrays($array, $args = array('votes' => 'desc')){
	    usort( $array, function( $a, $b ) use ( $args ){
	        $res = 0;

	        $a = (object) $a;
	        $b = (object) $b;

	        foreach( $args as $k => $v ){
	            if( $a->$k == $b->$k ) continue;

	            $res = ( $a->$k < $b->$k ) ? -1 : 1;
	            if( $v=='desc' ) $res= -$res;
	            break;
	        }

	        return $res;
	    } );

	    return $array;
	}
	public static function prepareItems($items) {
	    if(!$items)
	        return '';
	    $items = json_decode($items, true);
	    $items_str = '';
	    if(is_array($items))
		    foreach($items as $name => $qty) {
		        if($qty)
		            $items_str.= $name.': <b>'.$qty.' шт.</b>,';
		    }
	    return substr($items_str,0,-1);
	}

	public static function prepareProducts($items) {
	    if(!$items)
	        return '';
	    $items = json_decode($items, true);
	    $items_str = '';
	    if(is_array($items))
		    foreach($items as $key => $item) {
		        if($item['count'])
		            $items_str.= $item['name'].': <b>'.$item['count'].' шт.</b>,';
		    }
	    return substr($items_str,0,-1);
	}
	public static function convertToMins($time) {

	    if(strstr($time, ':'))// {
	        list($i, $s) = explode(':', $time);
	    if(strstr($time, '.'))// {
	        list($i, $s) = explode('.', $time);
	    if(isset($i))
	        return (int)$i*60 + (int)$s;
	    else
	        return 0;
	}

	public static function isJson($value) {
		if(is_array($value))
			$value = json_encode($value);
		if(!is_array(json_decode($value, true)))
            return false;

       	return true;
		// json_decode($value, true);

		// return json_last_error() === JSON_ERROR_NONE;
	}
}