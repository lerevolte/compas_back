<script type="text/javascript">
	@if(isset($_COOKIE['location']))
	$(document).ready(function(){
		$.ajax({
	      "url": "/set_driver_position",
	      "data": {'position': '{{ $_COOKIE['location'] }}', '_token': $('input[name=_token]').val()},
	      "dataType": "json",
	      "type": "POST",
	      "success": function (res) {
	        console.log(res)
	      }
	    });
	});
	@endif
</script>