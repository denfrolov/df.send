$(document).on('submit', '.df_ajax_form', function (event) {
	event.preventDefault();
	let form = $(this);
	let formData = new FormData(form[0]);
	form.find('button').css({
		'pointer-events': 'none',
		'opacity': '0.3'
	})
	$.ajax({
		url: form.attr('action'),
		data: formData,
		type: "POST",
		contentType: false,
		processData: false,
		dataType: "html",
		cache: false
	}).done(function (data) {
		if ($(data).find('.df_result').length) {
			form[0].reset();
			form.find('button').css({
				'pointer-events': 'auto',
				'opacity': '1'
			});
			alert($(data).find('.df_result').text());
		} else {
			alert('Что-то пошло не так,<br> перезагрузите страницу и попробуйте снова');
		}
	});
});