
@if ($crud->hasAccess('delete'))
<a href="javascript:void(0)" onclick="getFilter(event)" class="btn btn-sm btn-secondary" data-toggle="popover" id='deletebyfilter'><i class="fa fa-trash-o"></i> delete by filters</a>
@endif


<script>
	if(typeof getFilter != 'function') {
	
		
		/**
		 * Gets the currently visible columns, any user-added ordering, and applied filters to the crud table, then sends that all to the downloadFromCrud route for server-side preparation of the download
		 * @param  {event} e The event that occured. Only used to get the target of the event (the button) so we can disable it during processing.
		 * @return {null}   Result of function is not returned - but the user is redirected to the download url once the file has been generated.
		 */
      	function getFilter(e) {


      		var target = e.target;

      		target.disabled = true;
			target.innerHTML = `<div class="spinner-border spinner-border-sm"></div> Preparing...`;
	      	// get user-specified parameters
			var columnsVisible = crud.table.columns().visible().toArray();
			var order = crud.table.order()
			
			var postObj = {
				
				'order':order,
			}
			if (confirm('¿Estás seguro de que deseas eliminar estos datos?')) {
				$.ajax({
					"url": "{{ url($crud->route . '/deleteByFilters') }}",
					"method":"POST",
					"data": postObj,
					"success": function(result) {
				
						console.log("success");
						location.reload();

					},
					"error": function(result){
					
						console.log("error");
					},
					"complete": function() {
						target.disabled = false;
						target.innerHTML = "<i class='fa fa-trash-o'></i> Data Deleted ";
					}
				})
			} else{
				target.disabled = false;
				target.innerHTML = "<i class='fa fa-trash-o'></i> delete by filters ";
			} 
		}
	 
	}
</script>