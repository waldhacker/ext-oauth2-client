let windowObjectReference = null;
let previousUrl = null;
document.addEventListener("DOMContentLoaded", function () {
  const btns = document.querySelectorAll('button[data-oauth2-provider]');
  btns.forEach(btn => {
    btn.addEventListener('click', (evt) => {
      openSignInWindow(evt.target.getAttribute("data-oauth2-provider"), 'oauth2-authenticate')
    })
  });
});

const openSignInWindow = (url, name) => {
  window.removeEventListener('message', receiveMessage);
  const strWindowFeatures =
    'toolbar=no, menubar=no, width=600, height=700, top=100, left=100';

  if (windowObjectReference === null || windowObjectReference.closed) {
    windowObjectReference = window.open(url, name, strWindowFeatures);
  } else if (previousUrl !== url) {
    windowObjectReference = window.open(url, name, strWindowFeatures);
    windowObjectReference.focus();
  } else {
    windowObjectReference.focus();
  }

  window.addEventListener('message', event => receiveMessage(event), false);
  previousUrl = url;
};


const receiveMessage = event => {
  console.log(event.origin)
  if (event.origin !== window.location.origin || event.source.origin !== window.location.origin) {
    // security check
    return false;
  }
  const {data} = event;
  const urlParams = new URLSearchParams(data);
  document.getElementById('oauth2-code').value = urlParams.get('code')
  document.getElementById('oauth2-state').value = urlParams.get('state')
  document.getElementById('oauth2-provider').value = urlParams.get('oauth2-provider')
  document.getElementById("oauth2-authorize").submit();
};
