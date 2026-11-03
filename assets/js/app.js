document.addEventListener('DOMContentLoaded', function () {
  // stop double-clicks creating duplicate registrations
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function () {
      f.querySelectorAll('button').forEach(function (b) { setTimeout(function () { b.disabled = true; }, 0); });
    });
  });
  // phone fields: keep only digits and +
  document.querySelectorAll('input[name="phone"]').forEach(function (i) {
    i.addEventListener('input', function () { i.value = i.value.replace(/[^0-9+]/g, ''); });
  });
  // PIN field: digits only, max 4
  document.querySelectorAll('input[name="pin"]').forEach(function (i) {
    i.addEventListener('input', function () { i.value = i.value.replace(/\D/g, '').slice(0, 4); });
  });
});