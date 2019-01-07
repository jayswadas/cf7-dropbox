document.addEventListener("DOMContentLoaded", function(event) {
	wpcf7_dropbox_mailsent_handler();
});

function wpcf7_dropbox_mailsent_handler() {
	document.addEventListener( 'wpcf7mailsent', function( event ) {
		form = wpcf7_dropbox_forms [ event.detail.contactFormId ];

		if ( form.access_token && form.file_input ) {
			var accessToken = form.access_token;
			var folder = form.folder;
			var dbx = new Dropbox({ accessToken: accessToken });

			var file_input_str = form.file_input;
			var file_inputs = file_input_str.split(",");
			wpcf7_dropbox_upload_file(file_inputs, folder, dbx, 0);
		}
		return false;

	}, false );
}

function wpcf7_dropbox_upload_file(file_inputs, folder, dbx, file_count) {
	if ( file_inputs.length > file_count ) {
		var file_input = document.getElementById( file_inputs[file_count].trim() );
		var file = file_input.files[0];

		if( file ) {
			var file_path = Boolean(folder) ? '/' + folder + '/' + file.name : '/' + file.name;

			dbx.filesUpload({ path: file_path, contents: file })
			.then(function(response) {
				console.log(response);
				console.log('File uploaded!');
			})
			.catch(function(error) {
				console.error(error);
			});
		}

	 	wpcf7_dropbox_upload_file( file_inputs, folder, dbx, file_count + 1 );
	}
}