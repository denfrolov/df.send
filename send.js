BX.ready(function () {
	document.querySelectorAll('.df_ajax_form').forEach(function (el) {
		el.addEventListener('submit', function (event) {
			event.preventDefault();
			let form = this;
			let formData = new FormData(form);
			BX.ajax.runComponentAction('df:df_messages', 'sendMessage', {
				mode: 'class',
				data: formData,
				signedParameters: form.querySelector('[name="signedParameters"]').value
			}).then(function (response) {
				if (response['status'] === 'success') {
					form.innerHTML = "<div class='df_result'>" + response['data']['success_text'] + "</div>"
				}
			}, function (response) {
				alert("Что-то пошло не так, перезагрузите страницу и попробуйте снова")
				console.log(response);
			});
		})
	})
});
