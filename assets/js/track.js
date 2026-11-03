(function () {
  var box = document.getElementById('tracker');
  if (!box) return;
  var body = new URLSearchParams({ code: box.dataset.code, pin: box.dataset.pin });
  var label = { pending: 'Pending', waiting: 'Waiting', in_service: 'In service', completed: 'Completed', skipped: 'Skipped' };

  function info(s) {
    if (s.status === 'waiting' || s.status === 'in_service') return s.ahead + ' ahead, about ' + s.est + ' min';
    if (s.status === 'pending') return 'About ' + s.est + ' min';
    return '-';
  }
  function render(d) {
    var rows = document.getElementById('rows'); rows.innerHTML = '';
    var steps = document.getElementById('steps'); steps.innerHTML = '';
    var foundNow = false;
    d.stages.forEach(function (s) {
      var tr = document.createElement('tr');
      [s.name, null, info(s)].forEach(function (txt, i) {
        var td = document.createElement('td');
        if (i === 1) { var b = document.createElement('span'); b.className = 'badge b-' + s.status; b.textContent = label[s.status]; td.appendChild(b); }
        else td.textContent = txt;
        tr.appendChild(td);
      });
      rows.appendChild(tr);
      var st = document.createElement('div');
      var done = s.status === 'completed' || s.status === 'skipped';
      var now = !done && !foundNow && (s.status === 'waiting' || s.status === 'in_service');
      if (now) foundNow = true;
      st.className = 'step' + (done ? ' done' : now ? ' now' : '');
      st.innerHTML = '<i></i>'; st.appendChild(document.createTextNode(s.name));
      steps.appendChild(st);
    });
    var m = d.remaining;
    document.getElementById('remaining').textContent = Math.floor(m / 60) + 'h ' + (m % 60) + 'm';
    var notes = document.getElementById('notes'); notes.innerHTML = '';
    d.notes.forEach(function (n) { var p = document.createElement('p'); p.textContent = '🔔 ' + n.message + ' (' + n.created_at + ')'; notes.appendChild(p); });
    document.getElementById('updated').textContent = 'Last updated ' + new Date().toLocaleTimeString();
  }
  function poll() {
    fetch('/medflow/api/status.php', { method: 'POST', body: body })
      .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
      .then(render).catch(function () {
        document.getElementById('updated').textContent = 'Connection problem, retrying...';
      });
  }
  poll(); setInterval(poll, 10000);
})();