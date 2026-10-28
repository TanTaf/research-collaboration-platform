(function () {
  'use strict';

  var toggle = document.getElementById('menuToggle');
  var scrim  = document.getElementById('navScrim');

  function closeNav() { document.body.classList.remove('nav-open'); }

  if (toggle) {
    toggle.addEventListener('click', function () {
      document.body.classList.toggle('nav-open');
    });
  }
  if (scrim) { scrim.addEventListener('click', closeNav); }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeNav(); }
  });

  document.querySelectorAll('.alert').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity .4s ease, transform .4s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-6px)';
      setTimeout(function () { el.remove(); }, 420);
    }, 5000);
  });

  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  var pw  = document.getElementById('password');
  var pw2 = document.getElementById('password_confirm');
  var hint = document.getElementById('pwHint');

  function checkPasswords() {
    if (!pw || !pw2 || !hint) { return; }
    if (pw2.value === '') { hint.textContent = ''; return; }
    if (pw.value === pw2.value) {
      hint.textContent = 'Passwords match.';
      hint.style.color = '#1F9D55';
    } else {
      hint.textContent = 'Passwords do not match yet.';
      hint.style.color = '#D6453D';
    }
  }
  if (pw)  { pw.addEventListener('input', checkPasswords); }
  if (pw2) { pw2.addEventListener('input', checkPasswords); }

  document.querySelectorAll('[data-tab-group]').forEach(function (group) {
    var name = group.getAttribute('data-tab-group');
    group.querySelectorAll('[data-tab]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-tab');
        group.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        document.querySelectorAll('[data-panel-group="' + name + '"]').forEach(function (p) {
          p.classList.toggle('hide', p.getAttribute('data-panel') !== target);
        });
      });
    });
  });

  document.querySelectorAll('[data-filter-input]').forEach(function (input) {
    input.addEventListener('input', function () {
      var needle = input.value.toLowerCase().trim();
      var sel    = input.getAttribute('data-filter-input');
      document.querySelectorAll(sel).forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().indexOf(needle) > -1 ? '' : 'none';
      });
    });
  });

  document.querySelectorAll('.chat-thread').forEach(function (box) {
    box.scrollTop = box.scrollHeight;
  });

  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = btn.getAttribute('data-copy');
      var done = function () {
        var old = btn.textContent;
        btn.textContent = 'Copied';
        setTimeout(function () { btn.textContent = old; }, 1600);
      };
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(done);
      } else {
        var ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); done(); } catch (err) { }
        document.body.removeChild(ta);
      }
    });
  });

  document.querySelectorAll('[data-max-select]').forEach(function (picker) {
    var max = parseInt(picker.getAttribute('data-max-select'), 10);
    picker.addEventListener('change', function () {
      var boxes = picker.querySelectorAll('input[type=checkbox]');
      var count = picker.querySelectorAll('input[type=checkbox]:checked').length;
      boxes.forEach(function (b) { b.disabled = (!b.checked && count >= max); });
    });
    picker.dispatchEvent(new Event('change'));
  });

  document.querySelectorAll('input[type=file]').forEach(function (input) {
    input.addEventListener('change', function () {
      var label = input.parentElement.querySelector('.field-hint');
      if (label && input.files.length) {
        label.textContent = 'Selected: ' + input.files[0].name;
        label.style.color = '#1F9D55';
      }
    });
  });
})();
