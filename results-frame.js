(function () {
  var frame = document.getElementById('resultsFrame');
  if (!frame) return;

  var params = new URLSearchParams(window.location.search);
  params.set('tab', 'results');

  frame.src = 'fixtures.php?' + params.toString();
})();
