BX.ready(function () {
	document.querySelector('.df_ajax_form').addEventListener('submit', function (event) {
		event.preventDefault();
		let form = this;
		let formData = new FormData(this);
		BX.ajax.runComponentAction('df:df_messages', 'sendMessage', {
			mode: 'class',
			data: formData,
			signedParameters: signedParameters
		}).then(function (response) {
			console.log(response);
			if (response['status'] === 'success') {
				form.innerHTML = "<div class='df_result'>" + response['data']['success_text'] + "</div>"
			}
		}, function (response) {
			//сюда будут приходить все ответы, у которых status !== 'success'
			console.log(response);
		});
	})
});