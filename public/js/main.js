function header(form) {
  if (!form.name.value || !form.value.value) {
    return '';
  }

  let header = `Set-Cookie: ${form.name.value}=${form.value.value}`;

  if (form.expires.checked) {
      if (!form.expdate.value) { return ''; }
      let date = new Date(form.expdate.value);
      header += '; expires=' + date.toUTCString();
  }

  if (form.path.value) {
      header += '; path=' + form.path.value;
  }

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

// set a hidden cookie with link protocol
document.querySelectorAll('a.self').forEach(a => a.addEventListener('click', function (e) {
  e.preventDefault(); // ensure the cookie gets set
  document.cookie = '!proto=' + new URL(e.target.href).protocol;
  window.location = e.target.href;
}));

document.querySelector('form').addEventListener('input', function (e) {
  if (e.target.name == 'expires') {
    // better than toggle when navigating in history
    this.expdate.parentNode.classList[e.target.checked ? 'remove' : 'add']('hidden');
    this.expdate.required = e.target.checked;
  }

  this.querySelector('samp').innerText = header(this);
  this.tz.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
  // gets ISO format of local datetime
  this.expdate.setAttribute('min', new Date().toLocaleString('sv').substr(0, 16).replace(' ', 'T'));
});

let cookieBox = document.querySelector('article');
let p = document.createElement('p');
p.innerText = document.cookie ? 'JavaScript received cookies:' : 'JavaScript received no cookies.';
cookieBox.appendChild(p);

if (document.cookie) {
  let ul = document.createElement('ul');
  cookieBox.appendChild(ul);

  for (let [name, value] of new URLSearchParams(document.cookie.replace(/; /g, '&'))) {
      let code = document.createElement('code');
      code.innerText = name + ' = ' + value;

      if (name[0] != '!') {
          let li = document.createElement('li');
          li.appendChild(code);
          ul.appendChild(li);
      }
  }
}

// show Fetch section for JS browsers
const fetchSection = document.querySelector('.fetch');
fetchSection.classList.remove('hidden');

function callFetch() {
  const data = new URLSearchParams(new FormData(document.querySelector('form')));
  const credentials = document.querySelector('input[name=credentials]:checked').value;
  fetchSection.querySelector('.error')?.remove();

  console.info('Calling Fetch API', {body: data.toString(), credentials});
  fetch('/', {
    method: 'POST',
    body: data,
    credentials,
  }).then(async res => {
    console.info('Fetch returned', res);
    const resText = await res.text();
    const parsedRes = new DOMParser().parseFromString(resText, 'text/html');
    const article = parsedRes.querySelector('article');

    console.info('Parsed response HTML:', article.innerHTML);
    const error = article.querySelector('.error');
    if (error) {
      fetchSection.appendChild(error);
    } else {
      document.querySelector('article').innerHTML = article.innerHTML;
      console.info('Updated displayed cookie info');
    }
  });
}
