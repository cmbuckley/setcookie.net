function header(form) {
  let header = `Set-Cookie: ${form.name.value}=${form.value.value}; path=/`;

  if (form.dom.value && form.dom.value != 'none') {
    form.dom.forEach(function (input) {
      if (input.value == form.dom.value) {
        header += '; domain=' + input.labels[0].innerText;
      }
    });
  }

  if (form.sec && form.sec.checked) {
    header += '; secure';
  }

  header += '; HttpOnly';

  if (form.ss.value && form.ss.value != 'notset') {
    form.ss.forEach(function (input) {
      if (input.value == form.ss.value) {
        header += '; SameSite=' + input.labels[0].innerText;
      }
    });
  }

  return header;
}

document.querySelector('form').addEventListener('input', function () {
  if (this.name.value && this.value.value) {
    this.querySelector('samp').innerText = header(this);
  }
});
