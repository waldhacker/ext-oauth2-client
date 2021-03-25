const params = window.location.search;
if (window.opener) {
  window.opener.postMessage(params);
  window.close();
}
