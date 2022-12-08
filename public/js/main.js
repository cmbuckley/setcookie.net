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

  if (form.httponly && form.httponly.checked) {
     header += '; HttpOnly';
  }

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

let cookieBox = document.querySelector('article');
let p = document.createElement('p');
p.innerText = document.cookie ? 'JavaScript received cookies:' : 'JavaScript received no cookies.';
cookieBox.appendChild(p);

if (document.cookie) {
  let ul = document.createElement('ul');
  cookieBox.appendChild(ul);

  for (let cookie of new URLSearchParams(document.cookie.replace(/; /g, '&'))) {
      let code = document.createElement('code');
      code.innerText = cookie[0] + ' = ' + cookie[1];

      let li = document.createElement('li');
      li.appendChild(code);
      ul.appendChild(li);
  }
}
