$('.alert').alert().delay(3000).slideUp('slow');

$(function () {

	// untuk add menu dan edit menu
	$('.add-menu').on('click', function () {
		$('#menuModalLabel').html('Add New Menu');
		$('.modal-footer button[type=submit]').html('Add');
		$('#menu').val('');
	});


	$('.btn-edit-menu').on('click', function () {
		const id = $(this).data('id');
		const url = $(this).data('url');

		$('#menuModalLabel').html('Edit Menu');
		$('.modal-footer button[type=submit]').html('Save');
		$('.modal-content form').attr('action', `${url}/menu/editMenu/${id}`);


		fetch(url + 'menu/getMenu/' + id)
			.then(data => data.json())
			.then(data => {
				$('#menu').val(data.menu);
			});
	});



	// untuk add submenu dan edit submenu
	$('.add-submenu').on('click', function () {
		$('#submenuModalLabel').html('Add New Submenu');
		$('.modal-footer button[type=submit]').html('Add');
		$('#title').val('');
		$('#menu_id').val('');
		$('#url').val('');
		$('#icon').val('');
		// $('#is_active').attr('checked', 'checked');
	});


	$('.btn-edit-submenu').on('click', function () {
		const id = $(this).data('id');
		const url = $(this).data('url');

		$('#submenuModalLabel').html('Edit Submenu');
		$('.modal-footer button[type=submit]').html('Save');
		$('.modal-content form').attr('action', `${url}/menu/editSubmenu/${id}`);


		fetch(url + 'menu/getSubmenu/' + id)
			.then(data => data.json())
			.then(data => {
				$('#title').val(data.title);
				$('#menu_id').val(data.menu_id);
				$('#url').val(data.url);
				$('#icon').val(data.icon);
				// $('#is_active').val(data.is_active);
			});
	});
});




// untuk edit profile (image)
$('.custom-file-input').on('change', function () {
	let fileName = $(this).val().split('\\').pop();
	$(this).next('.custom-file-label').addClass("selected").html(fileName);
});





// sweet alert on menu delete
$('.btn-delete-menu').on('click', function (event) {
	event.preventDefault();

	const name = $(this).data('menu');
	const href = $(this).attr('href');

	sweetAlert(name, href);
});

// sweet alert on submenu delete
$('.btn-delete-submenu').on('click', function (event) {
	event.preventDefault();

	const name = $(this).data('title');
	const href = $(this).attr('href');

	sweetAlert(name, href);
});


// Swal delete Function
function sweetAlert(name, href) {
	Swal.fire({
		title: 'Are you sure?',
		text: `"${name}" will be deleted`,
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Delete'
	}).then((result) => {
		if (result.value) {
			document.location.href = href;
		}
	});
}



// ajax utk check/uncheck role access
$('.form-check-input').on('click', function () {
	const menuId = $(this).data('menu');
	const roleId = $(this).data('role');
	const url = $(this).data('url');

	$.ajax({
		url: `${url}admin/changeaccess`,
		type: "POST",
		data: {
			menuId: menuId,
			roleId: roleId
		},
		success: function () {
			document.location.href = `${url}/admin/roleaccess/${roleId}`;
		}
	});
});
