jQuery(document).ready(function() {
    wpcf7_dropbox_mailsent_handler();
});

function wpcf7_dropbox_mailsent_handler() {
	document.addEventListener( 'wpcf7mailsent', function( event ) {
		form = wpcf7_dropbox_forms [ event.detail.contactFormId ];

		if ( form.access_token && form.file_input ) {
			var accessToken = form.access_token;
			var dbx = new Dropbox({ accessToken: accessToken });
			var fileInput = document.getElementById(form.file_input);
			var file = fileInput.files[0];
			if(file) {
				dbx.filesUpload({path: '/' + file.name, contents: file})
				.then(function(response) {
				  console.log(response);
				  console.log('File uploaded!');
				})
				.catch(function(error) {
				  console.error(error);
				});
			}
		}
		return false;

	}, false );
}